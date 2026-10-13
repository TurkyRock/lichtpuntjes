<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('news:import')->everyThreeHours()->withoutOverlapping(180);
