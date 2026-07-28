<?php

namespace App\Support;

/**
 * The fixed hourly slots offered by "Auto send reports"
 * (logins-config.html): 6:00 AM through 12:00 AM. Written out rather than
 * generated, so changing the granularity is a deliberate edit here and in
 * the wireframe rather than two loops drifting apart. Keys are the 24-hour
 * values stored in center_settings.auto_send_report_time.
 */
class AutoSendReportTimes
{
    /** @var array<string, string> "H:i" => 12-hour label, in wireframe order. */
    public const array OPTIONS = [
        '06:00' => '6:00 AM', '07:00' => '7:00 AM', '08:00' => '8:00 AM', '09:00' => '9:00 AM',
        '10:00' => '10:00 AM', '11:00' => '11:00 AM', '12:00' => '12:00 PM',
        '13:00' => '1:00 PM', '14:00' => '2:00 PM', '15:00' => '3:00 PM', '16:00' => '4:00 PM',
        '17:00' => '5:00 PM', '18:00' => '6:00 PM', '19:00' => '7:00 PM', '20:00' => '8:00 PM',
        '21:00' => '9:00 PM', '22:00' => '10:00 PM', '23:00' => '11:00 PM', '00:00' => '12:00 AM',
    ];

    public const string DEFAULT = '18:00';
}
