<?php

/**
 * This file is part of FusionInvoice.
 *
 * (c) FusionInvoice, LLC <jessedterry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FI\Modules\Invoices\Models;

use FI\Events\InvoiceItemSaving;
use FI\Events\InvoiceModified;
use FI\Support\CurrencyFormatter;
use FI\Support\NumberFormatter;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $guarded = ['id', 'item_id'];

    public static function boot()
    {
        parent::boot();

        static::saving(function($invoiceItem)
        {
            event(new InvoiceItemSaving($invoiceItem));
        });

        static::saved(function($invoiceItem)
        {
            event(new InvoiceModified($invoiceItem->invoice));
        });

        static::deleting(function ($invoiceItem)
        {
            $invoiceItem->amount()->delete();
        });

        static::deleted(function($invoiceItem)
        {
            if ($invoiceItem->invoice)
            {
                event(new InvoiceModified($invoiceItem->invoice));
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function amount()
    {
        return $this->hasOne('FI\Modules\Invoices\Models\InvoiceItemAmount', 'item_id');
    }

    public function invoice()
    {
        return $this->belongsTo('FI\Modules\Invoices\Models\Invoice');
    }

    public function taxRate()
    {
        return $this->belongsTo('FI\Modules\TaxRates\Models\TaxRate');
    }

    public function taxRate2()
    {
        return $this->belongsTo('FI\Modules\TaxRates\Models\TaxRate', 'tax_rate_2_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedQuantityAttribute()
    {
        //return NumberFormatter::format($this->attributes['quantity'],null,0.0);
        return NumberFormatter::format2($this->attributes['quantity'],null,0.0);
    }

    public function getFormattedNumericPriceAttribute()
    {
        return NumberFormatter::format($this->attributes['price']);
    }

    public function getFormattedPriceAttribute()
    {
        return CurrencyFormatter::format($this->attributes['price'], $this->invoice->currency);
    }

    public function getFormattedDescriptionAttribute()
    {
        return nl2br($this->attributes['description']);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereIn('invoice_id', function ($query) use ($from, $to)
        {
            $query->select('id')
                ->from('invoices')
                ->where('invoice_date', '>=', $from)
                ->where('invoice_date', '<=', $to);
        });
    }
}
