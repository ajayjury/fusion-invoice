<?php

/**
 * This file is part of FusionInvoice.
 *
 * (c) FusionInvoice, LLC <jessedterry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FI\Modules\PaymentMethods\Controllers;

use FI\Http\Controllers\Controller;
use FI\Modules\PaymentMethods\Models\PaymentMethod;
use FI\Modules\PaymentMethods\Requests\PaymentMethodRequest;
use FI\Traits\ReturnUrl;

class PaymentMethodController extends Controller
{
    use ReturnUrl;

    public function index()
    {
        $this->setReturnUrl();

        $paymentMethods = PaymentMethod::sortable(['name' => 'asc'])->paginate(config('fi.resultsPerPage'));

        return view('payment_methods.index')
            ->with('paymentMethods', $paymentMethods);
    }

    public function create()
    {
        return view('payment_methods.form')
            ->with('editMode', false);
    }

    public function store(PaymentMethodRequest $request)
    {
        PaymentMethod::create($request->all());

        return redirect($this->getReturnUrl())
            ->with('alertSuccess', trans('fi.record_successfully_created'));
    }

    public function edit($id)
    {
        $paymentMethod = PaymentMethod::find($id);

        return view('payment_methods.form')
            ->with(['editMode' => true, 'paymentMethod' => $paymentMethod]);
    }

    public function update(PaymentMethodRequest $request, $id)
    {
        $paymentMethod = PaymentMethod::find($id);

        $paymentMethod->fill($request->all());

        $paymentMethod->save();

        return redirect($this->getReturnUrl())
            ->with('alertInfo', trans('fi.record_successfully_updated'));
    }

    public function delete($id)
    {
        PaymentMethod::destroy($id);

        return redirect()->route('paymentMethods.index')
            ->with('alert', trans('fi.record_successfully_deleted'));
    }
}