<?php

namespace UbeeDev\LibBundle\Service;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpreadsheetExporter
{
    private $formatter;

    public function __construct($formatter = null)
    {
        $this->formatter = $formatter;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function exportSpreadSheet(string $title, array $data, array $descriptors): Spreadsheet
    {
        // reset array index because doctrine returns id as keys
        $data = array_values($data);
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()->setCreator("UbeeDev")
            ->setTitle($title);
        $excel = $spreadsheet->setActiveSheetIndex(0);

        $descriptors = array_values($descriptors);

        $excel = $this->addHeaders($excel, $descriptors);

        foreach ($data as $key => $item) {
            $lineNumber = ($key + 2);

            foreach ($descriptors as $columnIndex => $descriptor) {
                $formatFunctionName = $descriptor[2] ?? null;
                $values = $this->getValues($item, $descriptor);
                $column = $columnIndex + 1;

                $formattedValue = $formatFunctionName ? $this->formatter->$formatFunctionName(...$values) : $values[0];

                $excel->setCellValueByColumnAndRow($column, $lineNumber, $formattedValue);

                if($formattedValue instanceof \DateTimeInterface) {
                    $excel->getStyleByColumnAndRow($column, $lineNumber)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_DATE_DATETIME);
                }
            }

        }

        return $spreadsheet;
    }

    private function addHeaders(Worksheet $excel, $descriptors): Worksheet
    {
        //headers
        foreach ($descriptors as $key => $descriptor) {
            $headerText = $descriptor[0];
            $excel->setCellValueByColumnAndRow($key+1, 1, $headerText);
        }

        return $excel;
    }

    private function getValues($item, $descriptor): array
    {
        $values = [];

        if($descriptor[1]) {
            $instructions = is_string($descriptor[1]) ? [$descriptor[1]] : $descriptor[1];
            $extraParams = $descriptor[3] ?? [];

            foreach ($instructions as $instruction) {
                $methods = explode('.', $instruction);
                $value = $item;
                foreach ($methods as $method) {
                    $get = 'get'.ucfirst($method);
                    $is = 'is'.ucfirst($method);
                    $was = 'was'.ucfirst($method);
                    $has = 'has'.ucfirst($method);
                    if(method_exists($value, $get)) {
                        $value = $value->$get(...$extraParams);
                    } elseif(method_exists($value, $is)) {
                        $value = $value->$is($extraParams);
                    } elseif(method_exists($value, $was)) {
                        $value = $value->$was(...$extraParams);
                    } elseif(method_exists($value, $has)) {
                        $value = $value->$has(...$extraParams);
                    } elseif(method_exists($value, $method)) {
                        $value = $value->$method(...$extraParams);
                    } else {
                        throw new \Exception('Method '.$method.' doesn\'t exist');
                    }
                    if(is_null($value)) {
                        break;
                    }
                }
                $values[] = $value;
            }
        } else {
            $values = [$item];
        }

        return $values;
    }
}