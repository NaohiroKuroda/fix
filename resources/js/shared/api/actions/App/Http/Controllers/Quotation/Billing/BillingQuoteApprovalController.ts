import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:18
* @route '/quotation-management/billing-quote-approval'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/quotation-management/billing-quote-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:18
* @route '/quotation-management/billing-quote-approval'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:18
* @route '/quotation-management/billing-quote-approval'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:18
* @route '/quotation-management/billing-quote-approval'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:18
* @route '/quotation-management/billing-quote-approval'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:18
* @route '/quotation-management/billing-quote-approval'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::index
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:18
* @route '/quotation-management/billing-quote-approval'
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
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:30
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
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:30
* @route '/quotation-management/billing-quote-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:30
* @route '/quotation-management/billing-quote-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:30
* @route '/quotation-management/billing-quote-approval'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:30
* @route '/quotation-management/billing-quote-approval'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:56
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
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:56
* @route '/quotation-management/billing-quote-approval/reject'
*/
reject.url = (options?: RouteQueryOptions) => {
    return reject.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:56
* @route '/quotation-management/billing-quote-approval/reject'
*/
reject.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:56
* @route '/quotation-management/billing-quote-approval/reject'
*/
const rejectForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingQuoteApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingQuoteApprovalController.php:56
* @route '/quotation-management/billing-quote-approval/reject'
*/
rejectForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

reject.form = rejectForm

const BillingQuoteApprovalController = { index, confirm, reject }

export default BillingQuoteApprovalController