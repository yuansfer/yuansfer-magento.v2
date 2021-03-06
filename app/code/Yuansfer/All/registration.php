<?php
/**
 * Copyright © 2020 Yuansfer. All rights reserved.
 * See COPYING.txt for license details.
 */
require_once(BP.'/lib/internal/yuansfer/Error/ErrorBase.php');
require_once(BP.'/lib/internal/yuansfer/Error/Api.php');
require_once(BP.'/lib/internal/yuansfer/CurlClient.php');
require_once(BP.'/lib/internal/yuansfer/Requestor.php');
require_once(BP.'/lib/internal/yuansfer/MobileDetect.php');

\Magento\Framework\Component\ComponentRegistrar::register(
	\Magento\Framework\Component\ComponentRegistrar::MODULE,
	'Yuansfer_All',
	__DIR__
);
