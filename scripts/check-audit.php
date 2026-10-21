<?php

// These exact Laravel-only findings are accepted while the requested framework freeze remains.
// New advisory IDs or findings in any other package fail the build.
$accepted = ['PKSA-d5tc-s1qs-h781', 'PKSA-m5cs-t1y6-qpcs', 'PKSA-3r5d-mb8f-1qw9', 'PKSA-mdq4-51ck-6kdq'];
exec('composer audit --format=json', $output, $status);
$report = json_decode(implode("\n", $output), true);
if (! is_array($report) || ! isset($report['advisories']) || $status > 3) {
    fwrite(STDERR, "Dependency audit unavailable or invalid.\n");
    exit(1);
}
$failed = ! empty($report['abandoned']);
foreach ($report['advisories'] as $package => $advisories) {
    foreach ($advisories as $advisory) {
        $known = $package === 'laravel/framework' && in_array($advisory['advisoryId'], $accepted, true);
        echo ($known ? 'ACCEPTED FRAMEWORK FREEZE: ' : 'NEW SECURITY FINDING: ').$package.' '.$advisory['advisoryId'].' '.$advisory['title'].PHP_EOL;
        $failed = $failed || ! $known;
    }
}
exit($failed ? 1 : 0);
