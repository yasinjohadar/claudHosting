<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketNote;
use App\Services\WhmcsApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TicketService
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
    }

    /**
     * الحصول على قائمة التذاكر من WHMCS
     *
     * @param int $limit
     * @param int $page
     * @param array $filters
     * @return array
     */
    public function getTicketsFromWhmcs($limit = 25, $page = 1, $filters = [])
    {
        return $this->whmcsApiService->getTickets($limit, $page, $filters);
    }

    /**
     * الحصول على قائمة التذاكر من قاعدة البيانات المحلية
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLocalTickets($limit = 25)
    {
        return Ticket::with(['customer', 'replies'])
            ->orderBy('date', 'desc')
            ->paginate($limit);
    }

    /**
     * مزامنة التذاكر من WHMCS إلى قاعدة البيانات المحلية
     *
     * @return array
     */
    public function syncTicketsFromWhmcs()
    {
        $results = [
            'success' => true,
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        try {
            $page = 1;
            $limit = 100;
            $hasMore = true;

            while ($hasMore) {
                $whmcsTickets = $this->whmcsApiService->getTickets($limit, $page);

                if (empty($whmcsTickets)) {
                    $hasMore = false;
                    continue;
                }

                foreach ($whmcsTickets as $whmcsTicket) {
                    // التحقق من وجود العميل
                    $customer = \App\Models\Customer::where('whmcs_id', $whmcsTicket['userid'])->first();
                    
                    if (!$customer) {
                        continue;
                    }

                    $ticket = Ticket::where('whmcs_id', $whmcsTicket['id'])->first();

                    if ($ticket) {
                        // تحديث التذكرة الموجودة
                        $ticket->update([
                            'whmcs_client_id' => $customer->whmcs_id,
                            'tid' => $whmcsTicket['tid'] ?? null,
                            'deptid' => $whmcsTicket['deptid'] ?? 1,
                            'userid' => $customer->whmcs_id,
                            'name' => $whmcsTicket['name'] ?? $customer->fullname,
                            'email' => $whmcsTicket['email'] ?? $customer->email,
                            'subject' => $whmcsTicket['subject'] ?? '',
                            'message' => $whmcsTicket['message'] ?? '',
                            'status' => $whmcsTicket['status'] ?? 'Open',
                            'priority' => $whmcsTicket['priority'] ?? 'Medium',
                            'admin' => $whmcsTicket['admin'] ?? null,
                            'lastreply' => !empty($whmcsTicket['lastreply']) ? date('Y-m-d H:i:s', strtotime($whmcsTicket['lastreply'])) : null,
                            'lastadminreply' => !empty($whmcsTicket['lastadminreply']) ? date('Y-m-d H:i:s', strtotime($whmcsTicket['lastadminreply'])) : null,
                            'date' => !empty($whmcsTicket['date']) ? date('Y-m-d H:i:s', strtotime($whmcsTicket['date'])) : now(),
                            'lastmodified' => !empty($whmcsTicket['lastmodified']) ? date('Y-m-d H:i:s', strtotime($whmcsTicket['lastmodified'])) : now(),
                            'service' => $whmcsTicket['service'] ?? null,
                            'synced_at' => now(),
                        ]);

                        $results['updated']++;
                    } else {
                        // إنشاء تذكرة جديدة
                        $ticket = Ticket::create([
                            'whmcs_id' => $whmcsTicket['id'],
                            'whmcs_client_id' => $customer->whmcs_id,
                            'tid' => $whmcsTicket['tid'] ?? null,
                            'deptid' => $whmcsTicket['deptid'] ?? 1,
                            'userid' => $customer->whmcs_id,
                            'name' => $whmcsTicket['name'] ?? $customer->fullname,
                            'email' => $whmcsTicket['email'] ?? $customer->email,
                            'subject' => $whmcsTicket['subject'] ?? '',
                            'message' => $whmcsTicket['message'] ?? '',
                            'status' => $whmcsTicket['status'] ?? 'Open',
                            'priority' => $whmcsTicket['priority'] ?? 'Medium',
                            'admin' => $whmcsTicket['admin'] ?? null,
                            'lastreply' => !empty($whmcsTicket['lastreply']) ? date('Y-m-d H:i:s', strtotime($whmcsTicket['lastreply'])) : null,
                            'lastadminreply' => !empty($whmcsTicket['lastadminreply']) ? date('Y-m-d H:i:s', strtotime($whmcsTicket['lastadminreply'])) : null,
                            'date' => !empty($whmcsTicket['date']) ? date('Y-m-d H:i:s', strtotime($whmcsTicket['date'])) : now(),
                            'lastmodified' => !empty($whmcsTicket['lastmodified']) ? date('Y-m-d H:i:s', strtotime($whmcsTicket['lastmodified'])) : now(),
                            'service' => $whmcsTicket['service'] ?? null,
                            'synced_at' => now(),
                        ]);

                        $results['created']++;
                    }
                    
                    // مزامنة ردود التذكرة
                    $this->syncTicketReplies($whmcsTicket['id'], $ticket->id);
                }

                $page++;
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error syncing tickets from WHMCS', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * مزامنة ردود التذكرة
     *
     * @param int $whmcsTicketId
     * @param int $localTicketId
     * @return void
     */
    private function syncTicketReplies($whmcsTicketId, $localTicketId)
    {
        try {
            $whmcsTicketDetails = $this->whmcsApiService->getTicketDetails($whmcsTicketId);
            
            if (!$whmcsTicketDetails || !isset($whmcsTicketDetails['replies']['reply'])) {
                return;
            }
            
            foreach ($whmcsTicketDetails['replies']['reply'] as $whmcsReply) {
                // التحقق من وجود العميل
                $customer = null;
                if (!empty($whmcsReply['userid'])) {
                    $customer = \App\Models\Customer::where('whmcs_id', $whmcsReply['userid'])->first();
                }
                
                // تحديد نوع الرد
                $type = 'client';
                if (!empty($whmcsReply['admin'])) {
                    $type = 'admin';
                } elseif (empty($whmcsReply['userid']) && empty($whmcsReply['admin'])) {
                    $type = 'system';
                }
                
                // البحث عن رد التذكرة
                $ticketReply = TicketReply::where('whmcs_id', $whmcsReply['id'])->first();
                
                if ($ticketReply) {
                    // تحديث رد التذكرة الموجود
                    $ticketReply->update([
                        'ticket_id' => $localTicketId,
                        'whmcs_ticket_id' => $whmcsTicketId,
                        'userid' => $customer ? $customer->whmcs_id : null,
                        'name' => $whmcsReply['name'] ?? ($customer ? $customer->fullname : 'System'),
                        'email' => $whmcsReply['email'] ?? ($customer ? $customer->email : 'system@localhost'),
                        'type' => $type,
                        'date' => !empty($whmcsReply['date']) ? date('Y-m-d H:i:s', strtotime($whmcsReply['date'])) : now(),
                        'message' => $whmcsReply['message'] ?? '',
                        'attachment' => $whmcsReply['attachment'] ?? null,
                        'admin' => $whmcsReply['admin'] ?? null,
                        'synced_at' => now(),
                    ]);
                } else {
                    // إنشاء رد تذكرة جديد
                    TicketReply::create([
                        'whmcs_id' => $whmcsReply['id'],
                        'ticket_id' => $localTicketId,
                        'whmcs_ticket_id' => $whmcsTicketId,
                        'userid' => $customer ? $customer->whmcs_id : null,
                        'name' => $whmcsReply['name'] ?? ($customer ? $customer->fullname : 'System'),
                        'email' => $whmcsReply['email'] ?? ($customer ? $customer->email : 'system@localhost'),
                        'type' => $type,
                        'date' => !empty($whmcsReply['date']) ? date('Y-m-d H:i:s', strtotime($whmcsReply['date'])) : now(),
                        'message' => $whmcsReply['message'] ?? '',
                        'attachment' => $whmcsReply['attachment'] ?? null,
                        'admin' => $whmcsReply['admin'] ?? null,
                        'synced_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing ticket replies', [
                'whmcs_ticket_id' => $whmcsTicketId,
                'local_ticket_id' => $localTicketId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * الحصول على التذاكر حسب العميل
     *
     * @param int $customerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTicketsByCustomer($customerId)
    {
        return Ticket::where('whmcs_client_id', $customerId)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * الحصول على التذاكر حسب الحالة
     *
     * @param string $status
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTicketsByStatus($status, $limit = 25)
    {
        return Ticket::where('status', $status)
            ->with(['customer'])
            ->orderBy('date', 'desc')
            ->paginate($limit);
    }

    /**
     * الحصول على التذاكر حسب الأولوية
     *
     * @param string $priority
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTicketsByPriority($priority, $limit = 25)
    {
        return Ticket::where('priority', $priority)
            ->with(['customer'])
            ->orderBy('date', 'desc')
            ->paginate($limit);
    }

    /**
     * الحصول على التذاكر المفتوحة
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOpenTickets($limit = 25)
    {
        return Ticket::whereIn('status', ['Open', 'Answered', 'Customer-Reply', 'In Progress'])
            ->with(['customer'])
            ->orderBy('date', 'desc')
            ->paginate($limit);
    }

    /**
     * الحصول على التذاكر المتأخرة
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOverdueTickets($limit = 25)
    {
        return Ticket::whereIn('status', ['Open', 'Answered', 'Customer-Reply', 'In Progress'])
            ->where(function ($query) {
                $query->where('priority', 'High')
                    ->orWhere('priority', 'Critical')
                    ->orWhere('priority', 'Emergency');
            })
            ->where(function ($query) {
                $query->whereNull('lastreply')
                    ->orWhere('lastreply', '<', now()->subDay());
            })
            ->with(['customer'])
            ->orderBy('priority', 'desc')
            ->paginate($limit);
    }

    /**
     * البحث عن التذاكر
     *
     * @param string $query
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchTickets($query, $limit = 25)
    {
        return Ticket::with(['customer'])
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                  ->orWhere('message', 'like', "%{$query}%")
                  ->orWhere('tid', 'like', "%{$query}%")
                  ->orWhereHas('customer', function ($q) use ($query) {
                      $q->where('firstname', 'like', "%{$query}%")
                        ->orWhere('lastname', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                  });
            })
            ->orderBy('date', 'desc')
            ->paginate($limit);
    }

    /**
     * إضافة رد على تذكرة في WHMCS
     *
     * @param int $ticketId
     * @param string $message
     * @return array
     */
    public function addReplyToTicket($ticketId, $message)
    {
        $results = [
            'success' => true,
            'errors' => []
        ];

        try {
            $ticket = Ticket::findOrFail($ticketId);
            
            $response = $this->whmcsApiService->addTicketReply($ticket->whmcs_id, $message);
            
            if ($response['success']) {
                // مزامنة التذكرة من WHMCS
                $this->syncSingleTicketFromWhmcs($ticket->whmcs_id);
            } else {
                $results['success'] = false;
                $results['errors'][] = $response['message'] ?? 'Unknown error';
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error adding reply to ticket', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * تحديث حالة التذكرة في WHMCS
     *
     * @param int $ticketId
     * @param string $status
     * @return array
     */
    public function updateTicketStatus($ticketId, $status)
    {
        $results = [
            'success' => true,
            'errors' => []
        ];

        try {
            $ticket = Ticket::findOrFail($ticketId);
            
            $response = $this->whmcsApiService->updateTicketStatus($ticket->whmcs_id, $status);
            
            if ($response['success']) {
                // تحديث الحالة محلياً
                $ticket->update([
                    'status' => $status,
                    'lastmodified' => now(),
                ]);
            } else {
                $results['success'] = false;
                $results['errors'][] = $response['message'] ?? 'Unknown error';
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error updating ticket status', [
                'ticket_id' => $ticketId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * مزامنة تذكرة واحدة من WHMCS
     *
     * @param int $whmcsTicketId
     * @return array
     */
    private function syncSingleTicketFromWhmcs($whmcsTicketId)
    {
        $results = [
            'success' => true,
            'created' => false,
            'updated' => false,
            'ticket' => null,
            'errors' => []
        ];

        try {
            $whmcsTicketDetails = $this->whmcsApiService->getTicketDetails($whmcsTicketId);

            if (!$whmcsTicketDetails || !isset($whmcsTicketDetails['id'])) {
                $results['success'] = false;
                $results['errors'][] = 'Ticket not found in WHMCS';
                return $results;
            }

            // التحقق من وجود العميل
            $customer = \App\Models\Customer::where('whmcs_id', $whmcsTicketDetails['userid'])->first();
            
            if (!$customer) {
                $results['success'] = false;
                $results['errors'][] = 'Customer not found';
                return $results;
            }

            $ticket = Ticket::where('whmcs_id', $whmcsTicketId)->first();

            if ($ticket) {
                // تحديث التذكرة الموجودة
                $ticket->update([
                    'whmcs_client_id' => $customer->whmcs_id,
                    'tid' => $whmcsTicketDetails['tid'] ?? null,
                    'deptid' => $whmcsTicketDetails['deptid'] ?? 1,
                    'userid' => $customer->whmcs_id,
                    'name' => $whmcsTicketDetails['name'] ?? $customer->fullname,
                    'email' => $whmcsTicketDetails['email'] ?? $customer->email,
                    'subject' => $whmcsTicketDetails['subject'] ?? '',
                    'message' => $whmcsTicketDetails['message'] ?? '',
                    'status' => $whmcsTicketDetails['status'] ?? 'Open',
                    'priority' => $whmcsTicketDetails['priority'] ?? 'Medium',
                    'admin' => $whmcsTicketDetails['admin'] ?? null,
                    'lastreply' => !empty($whmcsTicketDetails['lastreply']) ? date('Y-m-d H:i:s', strtotime($whmcsTicketDetails['lastreply'])) : null,
                    'lastadminreply' => !empty($whmcsTicketDetails['lastadminreply']) ? date('Y-m-d H:i:s', strtotime($whmcsTicketDetails['lastadminreply'])) : null,
                    'date' => !empty($whmcsTicketDetails['date']) ? date('Y-m-d H:i:s', strtotime($whmcsTicketDetails['date'])) : now(),
                    'lastmodified' => !empty($whmcsTicketDetails['lastmodified']) ? date('Y-m-d H:i:s', strtotime($whmcsTicketDetails['lastmodified'])) : now(),
                    'service' => $whmcsTicketDetails['service'] ?? null,
                    'synced_at' => now(),
                ]);

                $results['updated'] = true;
            } else {
                // إنشاء تذكرة جديدة
                $ticket = Ticket::create([
                    'whmcs_id' => $whmcsTicketDetails['id'],
                    'whmcs_client_id' => $customer->whmcs_id,
                    'tid' => $whmcsTicketDetails['tid'] ?? null,
                    'deptid' => $whmcsTicketDetails['deptid'] ?? 1,
                    'userid' => $customer->whmcs_id,
                    'name' => $whmcsTicketDetails['name'] ?? $customer->fullname,
                    'email' => $whmcsTicketDetails['email'] ?? $customer->email,
                    'subject' => $whmcsTicketDetails['subject'] ?? '',
                    'message' => $whmcsTicketDetails['message'] ?? '',
                    'status' => $whmcsTicketDetails['status'] ?? 'Open',
                    'priority' => $whmcsTicketDetails['priority'] ?? 'Medium',
                    'admin' => $whmcsTicketDetails['admin'] ?? null,
                    'lastreply' => !empty($whmcsTicketDetails['lastreply']) ? date('Y-m-d H:i:s', strtotime($whmcsTicketDetails['lastreply'])) : null,
                    'lastadminreply' => !empty($whmcsTicketDetails['lastadminreply']) ? date('Y-m-d H:i:s', strtotime($whmcsTicketDetails['lastadminreply'])) : null,
                    'date' => !empty($whmcsTicketDetails['date']) ? date('Y-m-d H:i:s', strtotime($whmcsTicketDetails['date'])) : now(),
                    'lastmodified' => !empty($whmcsTicketDetails['lastmodified']) ? date('Y-m-d H:i:s', strtotime($whmcsTicketDetails['lastmodified'])) : now(),
                    'service' => $whmcsTicketDetails['service'] ?? null,
                    'synced_at' => now(),
                ]);

                $results['created'] = true;
            }

            $results['ticket'] = $ticket;
            
            // مزامنة ردود التذكرة
            $this->syncTicketReplies($whmcsTicketId, $ticket->id);
            
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error syncing ticket from WHMCS', [
                'whmcs_ticket_id' => $whmcsTicketId,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * الحصول على إحصائيات التذاكر
     *
     * @return array
     */
    public function getTicketStatistics()
    {
        $totalTickets = Ticket::count();
        $openTickets = Ticket::whereIn('status', ['Open', 'Answered', 'Customer-Reply', 'In Progress'])->count();
        $closedTickets = Ticket::where('status', 'Closed')->count();
        $overdueTickets = Ticket::whereIn('status', ['Open', 'Answered', 'Customer-Reply', 'In Progress'])
            ->where(function ($query) {
                $query->where('priority', 'High')
                    ->orWhere('priority', 'Critical')
                    ->orWhere('priority', 'Emergency');
            })
            ->where(function ($query) {
                $query->whereNull('lastreply')
                    ->orWhere('lastreply', '<', now()->subDay());
            })
            ->count();
        
        $highPriorityTickets = Ticket::where('priority', 'High')->count();
        $criticalPriorityTickets = Ticket::where('priority', 'Critical')->count();
        $emergencyPriorityTickets = Ticket::where('priority', 'Emergency')->count();
        
        return [
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'closed_tickets' => $closedTickets,
            'overdue_tickets' => $overdueTickets,
            'high_priority_tickets' => $highPriorityTickets,
            'critical_priority_tickets' => $criticalPriorityTickets,
            'emergency_priority_tickets' => $emergencyPriorityTickets,
        ];
    }
}