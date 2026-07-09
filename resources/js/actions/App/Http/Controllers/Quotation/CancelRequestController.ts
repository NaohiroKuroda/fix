import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::index
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/quotation-management/cancel-request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::index
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::index
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::index
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::confirm
* @see app/Http/Controllers/Quotation/CancelRequestController.php:41
* @route '/quotation-management/cancel-request'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/quotation-management/cancel-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::confirm
* @see app/Http/Controllers/Quotation/CancelRequestController.php:41
* @route '/quotation-management/cancel-request'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::confirm
* @see app/Http/Controllers/Quotation/CancelRequestController.php:41
* @route '/quotation-management/cancel-request'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

const CancelRequestController = { index, confirm }

export default CancelRequestController