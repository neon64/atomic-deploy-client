<?php

namespace AtomicDeploy\Client\Vendor;

use Symfony\Component\Console\Output\OutputInterface;

interface VendorUpdater {

    public function transfer(OutputInterface $output, $directory, VendorTransferOperation $transfer);

}
