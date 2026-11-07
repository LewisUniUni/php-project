<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('outbox:relay')
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping(5);

Schedule::command('ops:heartbeat')
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping(2);
