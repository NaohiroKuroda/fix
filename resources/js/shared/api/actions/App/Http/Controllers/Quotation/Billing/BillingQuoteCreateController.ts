import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:18
* @route '/quotation-management/billing-quote-create'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/quotation-management/billing-quote-create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:18
* @route '/quotation-management/billing-quote-create'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:18
* @route '/quotation-management/billing-quote-create'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:18
* @route '/quotation-management/billing-quote-create'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:18
* @route '/quotation-management/billing-quote-create'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:18
* @route '/quotation-management/billing-quote-create'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:18
* @route '/quotation-management/billing-quote-create'
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
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:27
* @route '/quotation-management/billing-quote-create'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/quotation-management/billing-quote-create',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:27
* @route '/quotation-management/billing-quote-create'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:27
* @route '/quotation-management/billing-quote-create'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:27
* @route '/quotation-management/billing-quote-create'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteCreateController.php:27
* @route '/quotation-management/billing-quote-create'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const BillingQuoteCreateController = { index, store }

export default BillingQuoteCreateController