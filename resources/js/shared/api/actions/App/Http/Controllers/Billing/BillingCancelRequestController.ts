import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::index
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:17
* @route '/quotation-management/billing-cancel-request'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/quotation-management/billing-cancel-request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::index
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:17
* @route '/quotation-management/billing-cancel-request'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::index
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:17
* @route '/quotation-management/billing-cancel-request'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::index
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:17
* @route '/quotation-management/billing-cancel-request'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::index
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:17
* @route '/quotation-management/billing-cancel-request'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::index
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:17
* @route '/quotation-management/billing-cancel-request'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::index
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:17
* @route '/quotation-management/billing-cancel-request'
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
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::confirm
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:23
* @route '/quotation-management/billing-cancel-request'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/quotation-management/billing-cancel-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::confirm
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:23
* @route '/quotation-management/billing-cancel-request'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::confirm
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:23
* @route '/quotation-management/billing-cancel-request'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::confirm
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:23
* @route '/quotation-management/billing-cancel-request'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::confirm
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:23
* @route '/quotation-management/billing-cancel-request'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const BillingCancelRequestController = { index, confirm }

export default BillingCancelRequestController