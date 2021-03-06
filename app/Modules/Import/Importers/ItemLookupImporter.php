<?php

/**
 * This file is part of FusionInvoice.
 *
 * (c) FusionInvoice, LLC <jessedterry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FI\Modules\Import\Importers;

use FI\Modules\ItemLookups\Models\ItemLookup;
use FI\Modules\TaxRates\Models\TaxRate;
use Illuminate\Support\Facades\Validator;

class ItemLookupImporter extends AbstractImporter
{
    public function getFields()
    {
        return [
            'name'          => '* ' . trans('fi.name'),
            'description'   => '* ' . trans('fi.description'),
            'price'         => '* ' . trans('fi.price'),
            'tax_rate_id'   => trans('fi.tax_1'),
            'tax_rate_2_id' => trans('fi.tax_2'),
        ];
    }

    public function getMapRules()
    {
        return [
            'name'        => 'required',
            'description' => 'required',
            'price'       => 'required',
        ];
    }

    public function getValidator($input)
    {
        return Validator::make($input, [
                'name'  => 'required',
                'price' => 'required|numeric',
            ]
        );
    }

    public function importData($input)
    {
        $row = 1;

        $fields = [];

        $taxRates = TaxRate::get();

        foreach ($input as $field => $key)
        {
            if (is_numeric($key))
            {
                $fields[$key] = $field;
            }
        }

        $handle = fopen(storage_path('itemLookups.csv'), 'r');

        if (!$handle)
        {
            $this->messages->add('error', 'Could not open the file');

            return false;
        }

        while (($data = fgetcsv($handle, 1000, ',')) !== false)
        {
            if ($row !== 1)
            {
                $record = [];

                foreach ($fields as $key => $field)
                {
                    $record[$field] = $data[$key];
                }

                if (!isset($record['tax_rate_id']))
                {
                    $record['tax_rate_id'] = 0;
                }
                else
                {
                    if ($taxRate = $taxRates->where('name', $record['tax_rate_id'])->first())
                    {
                        $record['tax_rate_id'] = $taxRate->id;
                    }
                    else
                    {
                        $record['tax_rate_id'] = 0;
                    }
                }

                if (!isset($record['tax_rate_2_id']))
                {
                    $record['tax_rate_2_id'] = 0;
                }
                else
                {
                    if ($taxRate = $taxRates->where('name', $record['tax_rate_2_id'])->first())
                    {
                        $record['tax_rate_2_id'] = $taxRate->id;
                    }
                    else
                    {
                        $record['tax_rate_2_id'] = 0;
                    }
                }

                if ($this->validateRecord($record))
                {
                    ItemLookup::create($record);
                }
            }
            $row++;
        }

        fclose($handle);

        return true;
    }
}