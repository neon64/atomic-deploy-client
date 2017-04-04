<?php

namespace AtomicDeploy\Client\Vendor;

use Symfony\Component\Console\Output\OutputInterface;

interface VendorUpdater {

    public function update(OutputInterface $output, $directory, VendorUpdateOperation $transfer);

}
