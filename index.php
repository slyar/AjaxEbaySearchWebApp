<?php
header('Access-Control-Allow-Origin: *');
//error_reporting(E_ALL);
if (isset($_GET['keyword'])) {
	$result = "";
	$filterCount = 0;
	$ebayAPI = 'http://svcs.ebay.com/services/search/FindingService/v1';
	$respFormat = 'XML';
	$site = 'EBAY-US';
	$appid = 'usc2d0a82-e895-47ed-b95e-2dcb69e807c';
	$version = '1.0.0';

	$apicall = "$ebayAPI?OPERATION-NAME=findItemsAdvanced"
	. "&SERVICE-VERSION=$version"
	. "&GLOBAL-ID=$site"
	. "&SECURITY-APPNAME=$appid"
	. "&RESPONSE-DATA-FORMAT=$respFormat"
	. "&outputSelector(0)=SellerInfo"
	. "&outputSelector(1)=PictureURLSuperSize"
	. "&outputSelector(2)=StoreInfo";

	// keyword
	$keyword = trim($_GET['keyword']);
	$keyword = stripslashes($keyword);
	$keyword = htmlspecialchars($keyword);
	//echo 'keyword: <b>' . $keyword . '</b><br>';
	$apicall .= "&keywords=" . urlencode(utf8_encode($keyword));

	// MinPrice
	if (!empty($_GET['minprice'])) {
		$MinPrice = $_GET['minprice'];
		$apicall .= "&itemFilter($filterCount).name=MinPrice";
		$apicall .= "&itemFilter($filterCount).value=$MinPrice";
		$filterCount++;
	}


	// MaxPrice
	if (!empty($_GET['maxprice'])) {
		$MaxPrice = $_GET['maxprice'];
		$apicall .= "&itemFilter($filterCount).name=MaxPrice";
		$apicall .= "&itemFilter($filterCount).value=$MaxPrice";
		$filterCount++;
	}

	// Condition
	if (isset($_GET['condition'])) {
		$condition = $_GET['condition'];
		$apicall .= "&itemFilter($filterCount).name=Condition";
		for ($i=0; $i < count($condition); $i++) { 
			//echo 'condition: <b>' . $condition[$i] . '</b><br>';
			$apicall .= "&itemFilter($filterCount).value($i)=" . $condition[$i];
		}
		$filterCount++;
	}

	// ListingType
	if (isset($_GET['ListingType'])) {
		$ListingType = $_GET['ListingType'];
		$apicall .= "&itemFilter($filterCount).name=ListingType";
		for ($i=0; $i < count($ListingType); $i++) { 
			//echo 'ListingType: <b>' . $ListingType[$i] . '</b><br>';
			$apicall .= "&itemFilter($filterCount).value($i)=" . $ListingType[$i];
		}
		$filterCount++;
	}

	// ReturnsAcceptedOnly
	if (isset($_GET['ReturnsAcceptedOnly'])) {
		$ReturnsAcceptedOnly = $_GET['ReturnsAcceptedOnly'];
		//echo 'ReturnsAcceptedOnly: <b>' . $ReturnsAcceptedOnly . '</b><br>';
		$apicall .= "&itemFilter($filterCount).name=ReturnsAcceptedOnly";
		$apicall .= "&itemFilter($filterCount).value=true";
		$filterCount++;
	}

	// FreeShippingOnly
	if (isset($_GET['FreeShippingOnly'])) {
		$FreeShippingOnly = $_GET['FreeShippingOnly'];
		//echo 'FreeShippingOnly: <b>' . $FreeShippingOnly . '</b><br>';
		$apicall .= "&itemFilter($filterCount).name=FreeShippingOnly";
		$apicall .= "&itemFilter($filterCount).value=true";
		$filterCount++;
	}

	// ExpeditedShippingType
	if (isset($_GET['ExpeditedShippingType'])) {
		$ExpeditedShippingType = $_GET['ExpeditedShippingType'];
		//echo 'ExpeditedShippingType: <b>' . $ExpeditedShippingType . '</b><br>';
		$apicall .= "&itemFilter($filterCount).name=ExpeditedShippingType";
		$apicall .= "&itemFilter($filterCount).value=Expedited";
		$filterCount++;
	}

	// MaxHandlingTime
	if (!empty($_GET['MaxHandlingTime'])) {
		$MaxHandlingTime = $_GET['MaxHandlingTime'];
		$apicall .= "&itemFilter($filterCount).name=MAX_HANDLING_TIME";
		$apicall .= "&itemFilter($filterCount).value=$MaxHandlingTime";
		$filterCount++;
	}

	// Sort
	if (isset($_GET['sort'])) {
		$sortOrder = $_GET['sort'];
		//echo 'sort: <b>' . $sortOrder . '</b><br>';
		$apicall .= "&sortOrder=$sortOrder";
	}


	// itemsPerRange
	if (isset($_GET['itemsPerRange'])) {
		$itemsPerRange = $_GET['itemsPerRange'];
		//echo 'itemsPerRange: <b>' . $itemsPerRange . '</b><br>';
		$apicall .= "&paginationInput.entriesPerPage=$itemsPerRange";
	}

	// paginationInput.pageNumber
	if (isset($_GET['pageNumber'])) {
		$pageNumber = $_GET['pageNumber'];
		$apicall .= "&paginationInput.pageNumber=$pageNumber";
	}

	//echo $apicall;

	// Get XML result
	$xml = simplexml_load_file($apicall);

	function getValue($xml)
	{
		if ($xml) {
			return $xml->__toString();
		}
		else {
			return "";
		}
	}

	if ($xml && $xml->paginationOutput->totalEntries > 0) {
		$xmltojson = array(
			'ack' => $xml->ack->__toString(),
			'resultCount' => $xml->paginationOutput->totalEntries->__toString(),
			'pageNumber' => $xml->paginationOutput->pageNumber->__toString(),
			'itemCount' => $xml->paginationOutput->entriesPerPage->__toString(),
		);

		$seq = 0;
		foreach ($xml->searchResult->item as $xmlitem) {
			$xmltojson["item$seq"] = array(
				'basicInfo' => array(
					'title' => getValue($xmlitem->title),
					'viewItemURL' => getValue($xmlitem->viewItemURL),
					'galleryURL' => getValue($xmlitem->galleryURL),
					'pictureURLSuperSize' => getValue($xmlitem->pictureURLSuperSize),
					'convertedCurrentPrice' => getValue($xmlitem->sellingStatus->convertedCurrentPrice),
					'shippingServiceCost' => getValue($xmlitem->shippingInfo->shippingServiceCost),
					'conditionDisplayName' => getValue($xmlitem->condition->conditionDisplayName),
					'listingType' => getValue($xmlitem->listingInfo->listingType),
					'location' => getValue($xmlitem->location),
					'categoryName' => getValue($xmlitem->primaryCategory->categoryName),
					'topRatedListing' => getValue($xmlitem->topRatedListing),
				),
				'sellerInfo' => array(
						'sellerUserName' => getValue($xmlitem->sellerInfo->sellerUserName),
						'feedbackScore' => getValue($xmlitem->sellerInfo->feedbackScore),
						'positiveFeedbackPercent' => getValue($xmlitem->sellerInfo->positiveFeedbackPercent),
						'feedbackRatingStar' => getValue($xmlitem->sellerInfo->feedbackRatingStar),
						'topRatedSeller' => getValue($xmlitem->sellerInfo->topRatedSeller),
						'sellerStoreName' => getValue($xmlitem->storeInfo->storeName),
						'sellerStoreURL' => getValue($xmlitem->storeInfo->storeURL),
				),
				'shippingInfo' => array(
						'shippingType' => getValue($xmlitem->shippingInfo->shippingType),
						'shipToLocations' => getValue($xmlitem->shippingInfo->shipToLocations),
						'expeditedShipping' => getValue($xmlitem->shippingInfo->expeditedShipping),
						'oneDayShippingAvailable' => getValue($xmlitem->shippingInfo->oneDayShippingAvailable),
						'returnsAccepted' => getValue($xmlitem->returnsAccepted),
						'handlingTime' => getValue($xmlitem->shippingInfo->handlingTime),

				)
			);
			$seq++;
		}
	}
	else {
		$xmltojson = array(
			'ack' => 'No results found',
		);
	}

	//echo "<pre>";
	//echo json_encode($xmltojson, JSON_PRETTY_PRINT);
	echo json_encode($xmltojson);
	//echo "</pre>";


	// echo "<pre>";
	// echo json_encode($xml, JSON_PRETTY_PRINT);
	// echo "</pre>";
}
?>

