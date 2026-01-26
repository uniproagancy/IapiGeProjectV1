<?php

namespace App\Services\Google;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Drive;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleSheet
{
    private $client;
    private $sheetsService;

    /**
     * ✅ Initialize Google Sheets Service
     */
    public function __construct()
    {
        try {
            $this->client = new Client();
            $credentialsPath = storage_path('app/google.json');
            if (!file_exists($credentialsPath)) {
                throw new Exception('Google credentials file not found at: ' . $credentialsPath);
            }
            $this->client->setAuthConfig($credentialsPath);
            $this->client->addScope([
                Drive::DRIVE,
                Sheets::SPREADSHEETS,
                Sheets::SPREADSHEETS_READONLY
            ]);
            $this->sheetsService = new Sheets($this->client);
        } catch (Exception $e) {
            Log::error('Google Sheets Service Error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function getValues($spreadsheetId, $range)
    {
        try {
            $result = $this->sheetsService->spreadsheets_values->get($spreadsheetId, $range);
            $values = $result->getValues();
            $numRows = $values != null ? count($values) : 0;
            Log::info("Retrieved {$numRows} rows from Google Sheets");
            return $values;
        } catch (Exception $e) {
            Log::error('Error retrieving Google Sheets values: ' . $e->getMessage());
            throw new Exception('Failed to retrieve spreadsheet data: ' . $e->getMessage());
        }
    }

    public function getRow($spreadsheetId, $range)
    {
        $values = $this->getValues($spreadsheetId, $range);
        return $values ? $values[0] : null;
    }

    public function getColumn($spreadsheetId, $column, $sheetName = 'Sheet1')
    {
        try {
            $range = "{$sheetName}!{$column}:{$column}";
            return $this->getValues($spreadsheetId, $range);
        } catch (Exception $e) {
            Log::error('Error retrieving column: ' . $e->getMessage());
            return null;
        }
    }

    public function appendValues($spreadsheetId, $range, $values)
    {
        try {
            $body = new \Google\Service\Sheets\ValueRange([
                'values' => $values
            ]);
            $options = [
                'valueInputOption' => 'USER_ENTERED'
            ];
            $result = $this->sheetsService->spreadsheets_values->append(
                $spreadsheetId,
                $range,
                $body,
                $options
            );
            Log::info("Appended {$result->getUpdates()->getUpdatedRows()} rows to Google Sheets");
            return true;
        } catch (Exception $e) {
            Log::error('Error appending to Google Sheets: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ Update values in spreadsheet
     *
     * @param string $spreadsheetId
     * @param string $range
     * @param array $values - 2D array
     * @return bool
     */
    public function updateValues($spreadsheetId, $range, $values)
    {
        try {
            $body = new \Google\Service\Sheets\ValueRange([
                'values' => $values
            ]);

            $options = [
                'valueInputOption' => 'USER_ENTERED'
            ];

            $result = $this->sheetsService->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $body,
                $options
            );

            Log::info("Updated {$result->getUpdatedRows()} rows in Google Sheets");

            return true;

        } catch (Exception $e) {
            Log::error('Error updating Google Sheets: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ Clear values from range
     *
     * @param string $spreadsheetId
     * @param string $range
     * @return bool
     */
    public function clearValues($spreadsheetId, $range)
    {
        try {
            $this->sheetsService->spreadsheets_values->clear($spreadsheetId, $range);

            Log::info("Cleared values from range: {$range}");

            return true;

        } catch (Exception $e) {
            Log::error('Error clearing Google Sheets values: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ Get spreadsheet metadata (all sheets)
     *
     * @param string $spreadsheetId
     * @return array|null
     */
    public function getSpreadsheetMetadata($spreadsheetId)
    {
        try {
            $spreadsheet = $this->sheetsService->spreadsheets->get($spreadsheetId);
            return $spreadsheet;

        } catch (Exception $e) {
            Log::error('Error getting spreadsheet metadata: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Get all sheet names
     *
     * @param string $spreadsheetId
     * @return array
     */
    public function getSheetNames($spreadsheetId)
    {
        try {
            $spreadsheet = $this->getSpreadsheetMetadata($spreadsheetId);

            $sheetNames = [];
            foreach ($spreadsheet->getSheets() as $sheet) {
                $sheetNames[] = $sheet->getProperties()->getTitle();
            }

            return $sheetNames;

        } catch (Exception $e) {
            Log::error('Error getting sheet names: ' . $e->getMessage());
            return [];
        }
    }
}