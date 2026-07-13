import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::index
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/quotation-management/quote-request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::index
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::index
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::index
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::index
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::index
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::index
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

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

const QuoteRequestController = { index, send }

export default QuoteRequestController