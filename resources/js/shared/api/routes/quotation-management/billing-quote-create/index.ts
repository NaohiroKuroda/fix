import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:27
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
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:27
* @route '/quotation-management/billing-quote-create'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:27
* @route '/quotation-management/billing-quote-create'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:27
* @route '/quotation-management/billing-quote-create'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::store
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:27
* @route '/quotation-management/billing-quote-create'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const billingQuoteCreate = {
    store: Object.assign(store, store),
}

export default billingQuoteCreate