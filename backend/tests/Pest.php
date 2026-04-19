<?php

declare(strict_types=1);
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Smoke');
pest()->in('Unit');
