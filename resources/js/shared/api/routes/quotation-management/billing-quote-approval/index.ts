import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:24
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
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:24
* @route '/quotation-management/billing-quote-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:24
* @route '/quotation-management/billing-quote-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:24
* @route '/quotation-management/billing-quote-approval'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:24
* @route '/quotation-management/billing-quote-approval'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:40
* @route '/quotation-management/billing-quote-approval/reject'
*/
export const reject = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/quotation-management/billing-quote-approval/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:40
* @route '/quotation-management/billing-quote-approval/reject'
*/
reject.url = (options?: RouteQueryOptions) => {
    return reject.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:40
* @route '/quotation-management/billing-quote-approval/reject'
*/
reject.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:40
* @route '/quotation-management/billing-quote-approval/reject'
*/
const rejectForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:40
* @route '/quotation-management/billing-quote-approval/reject'
*/
rejectForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

reject.form = rejectForm

const billingQuoteApproval = {
    confirm: Object.assign(confirm, confirm),
    reject: Object.assign(reject, reject),
}

export default billingQuoteApproval