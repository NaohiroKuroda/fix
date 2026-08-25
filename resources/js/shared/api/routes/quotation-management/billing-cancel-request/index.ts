import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::confirm
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:22
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
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:22
* @route '/quotation-management/billing-cancel-request'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::confirm
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:22
* @route '/quotation-management/billing-cancel-request'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::confirm
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:22
* @route '/quotation-management/billing-cancel-request'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::confirm
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:22
* @route '/quotation-management/billing-cancel-request'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const billingCancelRequest = {
    confirm: Object.assign(confirm, confirm),
}

export default billingCancelRequest