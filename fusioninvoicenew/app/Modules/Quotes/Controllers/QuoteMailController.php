<?php

/**
 * This file is part of FusionInvoice.
 *
 * (c) FusionInvoice, LLC <jessedterry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FI\Modules\Quotes\Controllers;

use FI\Events\QuoteEmailed;
use FI\Events\QuoteEmailing;
use FI\Http\Controllers\Controller;
use FI\Modules\MailQueue\Support\MailQueue;
use FI\Modules\Quotes\Models\Quote;
use FI\Requests\SendEmailRequest;
use FI\Support\Contacts;
use FI\Support\Parser;

class QuoteMailController extends Controller
{
    private $mailQueue;

    public function __construct(MailQueue $mailQueue)
    {
        $this->mailQueue = $mailQueue;
    }

    public function create()
    {
        $quote = Quote::find(request('quote_id'));

        $contacts = new Contacts($quote->client);

        $parser = new Parser($quote);

        return view('quotes._modal_mail')
            ->with('quoteId', $quote->id)
            ->with('redirectTo', urlencode(request('redirectTo')))
            ->with('subject', $parser->parse('quoteEmailSubject'))
            ->with('body', $parser->parse('quoteEmailBody'))
            ->with('contactDropdownTo', $contacts->contactDropdownTo())
            ->with('contactDropdownCc', $contacts->contactDropdownCc())
            ->with('contactDropdownBcc', $contacts->contactDropdownBcc());
    }

    public function store(SendEmailRequest $request)
    {
        $quote = Quote::find($request->input('quote_id'));

        event(new QuoteEmailing($quote));

        $mail = $this->mailQueue->create($quote, $request->except('quote_id'));

        if ($this->mailQueue->send($mail->id))
        {
            event(new QuoteEmailed($quote));
        }
        else
        {
            return response()->json(['errors' => [[$this->mailQueue->getError()]]], 400);
        }
    }
}