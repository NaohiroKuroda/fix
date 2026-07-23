import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::send
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:41
* @route '/quotation-management/quote-request'
*/
export const send = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: send.url(options),
    method: 'post',
})

send.definition = {
    methods: ["post"],
    url: '/quotation-management/quote-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::send
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:41
* @route '/quotation-management/quote-request'
*/
send.url = (options?: RouteQueryOptions) => {
    return send.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::send
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:41
* @route '/quotation-management/quote-request'
*/
send.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: send.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::send
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:41
* @route '/quotation-management/quote-request'
*/
const sendForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: send.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::send
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:41
* @route '/quotation-management/quote-request'
*/
sendForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: send.url(options),
    method: 'post',
})

send.form = sendForm

const quoteRequest = {
    send: Object.assign(send, send),
}

export default quoteRequest