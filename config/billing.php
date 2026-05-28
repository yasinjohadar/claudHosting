<?php

return [

    'bank' => [
        'account_name' => env('BANK_ACCOUNT_NAME', ''),
        'iban' => env('BANK_IBAN', ''),
        'bank_name' => env('BANK_NAME', ''),
        'instructions' => env('BANK_INSTRUCTIONS', 'يرجى إجراء التحويل البنكي ثم إرفاق إثبات الدفع أو كتابة رقم العملية في الملاحظات.'),
    ],

];
