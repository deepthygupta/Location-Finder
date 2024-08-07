<?php

function getLocations($country, $city, $postalCode) {
    $apiKey = 'demo-key';
    $apiUrl = 'https://api.dhl.com/location-finder/v1/find-by-address';

    $queryParams = http_build_query([
        'countryCode' => $country,
        'addressLocality' => $city,
        'postalCode' => $postalCode
    ]);

    $ch = curl_init("{$apiUrl}?{$queryParams}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'DHL-API-Key: ' . $apiKey
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    
	if ($response === false) {
        throw new Exception('Failed to fetch data from DHL API');
    }

    return json_decode($response, true);
}

function filterLocations($locations) {
    return array_filter($locations, function ($location) {
        $address = $location['place']['address']['streetAddress'] ?? '';
        $workingHours = $location['openingHours'] ?? [];

        // Check if address has an odd number
        $addressParts = explode(' ', $address);
        $lastPart = end($addressParts);
        if (is_numeric($lastPart) && intval($lastPart) % 2 !== 0) {
            return false;
        }

        // Check if location works on weekends
        $worksOnSaturday = false;
        $worksOnSunday = false;

        foreach ($openingHours as $hours) {
            if (isset($hours['dayOfWeek'])) {
                $dayOfWeek = basename($hours['dayOfWeek']);
                if ($dayOfWeek === 'Saturday') {
                    $worksOnSaturday = true;
                } elseif ($dayOfWeek === 'Sunday') {
                    $worksOnSunday = true;
                }
            }
        }

        if (!$worksOnSaturday || !$worksOnSunday) {
            return false;
        }

        return true;
    });
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $country = $_POST['country'] ?? '';
    $city = $_POST['city'] ?? '';
    $postalCode = $_POST['postalCode'] ?? '';

    try {
        $locations = getLocations($country, $city, $postalCode);
        $filteredLocations = filterLocations($locations['locations']);

        echo json_encode($filteredLocations);
        exit;
    } catch (Exception $e) {
        print_r($e->getMessage());
        exit;
    }
}
?>

<html>
<body>
    <form method="POST">
        <label>Country:</label>
        <input type="text" id="country" name="country" required>
        <br>
        <label>City:</label>
        <input type="text" id="city" name="city" required>
        <br>
        <label>Postal Code:</label>
        <input type="text" id="postalCode" name="postalCode" required>
        <br>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
