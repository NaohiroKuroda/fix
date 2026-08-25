import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:22
* @route '/quotation-management/billing-quote-approval'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/quotation-management/billing-quote-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:22
* @route '/quotation-management/billing-quote-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:22
* @route '/quotation-management/billing-quote-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:22
* @route '/quotation-management/billing-quote-approval'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:22
* @route '/quotation-management/billing-quote-approval'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const billingQuoteApproval = {
    confirm: Object.assign(confirm, confirm),
}

export default billingQuoteApproval