<?php

use Illuminate\Support\Facades\Schedule;

return function (Schedule $schedule) {
    $schedule->job(new SendWeeklyExpenseReport)->weekly()->mondays()->at('08:00');
};
