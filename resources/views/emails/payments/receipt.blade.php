<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إشعار استلام دفعة</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 20px;">
    <div style="max-width: 680px; margin: 0 auto; background: #ffffff; border: 1px solid #e9ecef; border-radius: 8px; padding: 24px;">
        <h2 style="margin-top: 0; color: #1f2937;">تم استلام دفعتك بنجاح</h2>

        @if(!empty($templateBody))
            <div style="color: #374151; line-height: 1.7;">{!! $templateBody !!}</div>
        @endif

        <p style="color: #374151; line-height: 1.7;">
            عزيزي/عزيزتي {{ $customer->full_name ?: 'العميل الكريم' }}،
            <br>
            تم تسجيل دفعة جديدة على فاتورتك، وفيما يلي التفاصيل:
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
            <tbody>
            <tr>
                <td style="padding: 10px; border: 1px solid #e5e7eb; background: #f9fafb;">رقم الفاتورة</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb;">{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #e5e7eb; background: #f9fafb;">إجمالي الفاتورة</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb;">{{ number_format((float) $invoice->total, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #e5e7eb; background: #f9fafb;">المدفوع الآن</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb;">{{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #e5e7eb; background: #f9fafb;">إجمالي المدفوع</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb;">{{ number_format((float) $totalPaid, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #e5e7eb; background: #f9fafb;">المتبقي</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb;">{{ number_format((float) $balance, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #e5e7eb; background: #f9fafb;">تاريخ السداد</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb;">{{ optional($payment->date)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #e5e7eb; background: #f9fafb;">طريقة الدفع</td>
                <td style="padding: 10px; border: 1px solid #e5e7eb;">{{ $payment->payment_method_name }}</td>
            </tr>
            </tbody>
        </table>

        <p style="margin: 18px 0;">
            <a href="{{ route('client.invoices.show', $invoice) }}"
               style="background: #2563eb; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 6px; display: inline-block;">
                عرض الفاتورة
            </a>
        </p>

        <p style="color: #6b7280; font-size: 13px; margin-bottom: 0;">
            هذه رسالة آلية، في حال وجود استفسار يرجى التواصل مع فريق الدعم.
        </p>
    </div>
</body>
</html>
