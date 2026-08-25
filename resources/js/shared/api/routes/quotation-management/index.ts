import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import quoteRequestF3c656 from './quote-request'
import vendorSelection5181af from './vendor-selection'
import managerApproval3ec22d from './manager-approval'
import cancelRequestD640cf from './cancel-request'
import cancelApprovalB7fa4c from './cancel-approval'
import billingQuoteCreate082e7f from './billing-quote-create'
import billingQuoteApprovalD9abaa from './billing-quote-approval'
import billingCancelRequest259496 from './billing-cancel-request'
import billingCancelApprovalF8ce24 from './billing-cancel-approval'
import quotationMessages from './quotation-messages'
import commentAttachments from './comment-attachments'
/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
export const quoteRequest = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: quoteRequest.url(options),
    method: 'get',
})

quoteRequest.definition = {
    methods: ["get","head"],
    url: '/quotation-management/quote-request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
quoteRequest.url = (options?: RouteQueryOptions) => {
    return quoteRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
quoteRequest.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: quoteRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
quoteRequest.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: quoteRequest.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
const quoteRequestForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: quoteRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
quoteRequestForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: quoteRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/quotation-management/quote-request'
*/
quoteRequestForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: quoteRequest.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

quoteRequest.form = quoteRequestForm

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/quotation-management/vendor-selection'
*/
export const vendorSelection = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vendorSelection.url(options),
    method: 'get',
})

vendorSelection.definition = {
    methods: ["get","head"],
    url: '/quotation-management/vendor-selection',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/quotation-management/vendor-selection'
*/
vendorSelection.url = (options?: RouteQueryOptions) => {
    return vendorSelection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/quotation-management/vendor-selection'
*/
vendorSelection.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vendorSelection.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/quotation-management/vendor-selection'
*/
vendorSelection.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: vendorSelection.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/quotation-management/vendor-selection'
*/
const vendorSelectionForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vendorSelection.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/quotation-management/vendor-selection'
*/
vendorSelectionForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vendorSelection.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/quotation-management/vendor-selection'
*/
vendorSelectionForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vendorSelection.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

vendorSelection.form = vendorSelectionForm

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:27
* @route '/quotation-management/manager-approval'
*/
export const managerApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: managerApproval.url(options),
    method: 'get',
})

managerApproval.definition = {
    methods: ["get","head"],
    url: '/quotation-management/manager-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:27
* @route '/quotation-management/manager-approval'
*/
managerApproval.url = (options?: RouteQueryOptions) => {
    return managerApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:27
* @route '/quotation-management/manager-approval'
*/
managerApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: managerApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:27
* @route '/quotation-management/manager-approval'
*/
managerApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: managerApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:27
* @route '/quotation-management/manager-approval'
*/
const managerApprovalForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: managerApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:27
* @route '/quotation-management/manager-approval'
*/
managerApprovalForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: managerApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:27
* @route '/quotation-management/manager-approval'
*/
managerApprovalForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: managerApproval.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

managerApproval.form = managerApprovalForm

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
export const cancelRequest = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelRequest.url(options),
    method: 'get',
})

cancelRequest.definition = {
    methods: ["get","head"],
    url: '/quotation-management/cancel-request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
cancelRequest.url = (options?: RouteQueryOptions) => {
    return cancelRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
cancelRequest.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
cancelRequest.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cancelRequest.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
const cancelRequestForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
cancelRequestForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/quotation-management/cancel-request'
*/
cancelRequestForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cancelRequest.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

cancelRequest.form = cancelRequestForm

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/quotation-management/cancel-approval'
*/
export const cancelApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelApproval.url(options),
    method: 'get',
})

cancelApproval.definition = {
    methods: ["get","head"],
    url: '/quotation-management/cancel-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/quotation-management/cancel-approval'
*/
cancelApproval.url = (options?: RouteQueryOptions) => {
    return cancelApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/quotation-management/cancel-approval'
*/
cancelApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/quotation-management/cancel-approval'
*/
cancelApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cancelApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/quotation-management/cancel-approval'
*/
const cancelApprovalForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/quotation-management/cancel-approval'
*/
cancelApprovalForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/quotation-management/cancel-approval'
*/
cancelApprovalForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cancelApproval.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

cancelApproval.form = cancelApprovalForm

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::billingQuoteCreate
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:16
* @route '/quotation-management/billing-quote-create'
*/
export const billingQuoteCreate = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: billingQuoteCreate.url(options),
    method: 'get',
})

billingQuoteCreate.definition = {
    methods: ["get","head"],
    url: '/quotation-management/billing-quote-create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::billingQuoteCreate
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:16
* @route '/quotation-management/billing-quote-create'
*/
billingQuoteCreate.url = (options?: RouteQueryOptions) => {
    return billingQuoteCreate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::billingQuoteCreate
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:16
* @route '/quotation-management/billing-quote-create'
*/
billingQuoteCreate.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: billingQuoteCreate.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::billingQuoteCreate
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:16
* @route '/quotation-management/billing-quote-create'
*/
billingQuoteCreate.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: billingQuoteCreate.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::billingQuoteCreate
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:16
* @route '/quotation-management/billing-quote-create'
*/
const billingQuoteCreateForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingQuoteCreate.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::billingQuoteCreate
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:16
* @route '/quotation-management/billing-quote-create'
*/
billingQuoteCreateForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingQuoteCreate.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteCreateController::billingQuoteCreate
* @see app/Http/Controllers/Billing/BillingQuoteCreateController.php:16
* @route '/quotation-management/billing-quote-create'
*/
billingQuoteCreateForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingQuoteCreate.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

billingQuoteCreate.form = billingQuoteCreateForm

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::billingQuoteApproval
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:16
* @route '/quotation-management/billing-quote-approval'
*/
export const billingQuoteApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: billingQuoteApproval.url(options),
    method: 'get',
})

billingQuoteApproval.definition = {
    methods: ["get","head"],
    url: '/quotation-management/billing-quote-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::billingQuoteApproval
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:16
* @route '/quotation-management/billing-quote-approval'
*/
billingQuoteApproval.url = (options?: RouteQueryOptions) => {
    return billingQuoteApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::billingQuoteApproval
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:16
* @route '/quotation-management/billing-quote-approval'
*/
billingQuoteApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: billingQuoteApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::billingQuoteApproval
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:16
* @route '/quotation-management/billing-quote-approval'
*/
billingQuoteApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: billingQuoteApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::billingQuoteApproval
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:16
* @route '/quotation-management/billing-quote-approval'
*/
const billingQuoteApprovalForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingQuoteApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::billingQuoteApproval
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:16
* @route '/quotation-management/billing-quote-approval'
*/
billingQuoteApprovalForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingQuoteApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingQuoteApprovalController::billingQuoteApproval
* @see app/Http/Controllers/Billing/BillingQuoteApprovalController.php:16
* @route '/quotation-management/billing-quote-approval'
*/
billingQuoteApprovalForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingQuoteApproval.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

billingQuoteApproval.form = billingQuoteApprovalForm

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::billingCancelRequest
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:16
* @route '/quotation-management/billing-cancel-request'
*/
export const billingCancelRequest = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: billingCancelRequest.url(options),
    method: 'get',
})

billingCancelRequest.definition = {
    methods: ["get","head"],
    url: '/quotation-management/billing-cancel-request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::billingCancelRequest
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:16
* @route '/quotation-management/billing-cancel-request'
*/
billingCancelRequest.url = (options?: RouteQueryOptions) => {
    return billingCancelRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::billingCancelRequest
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:16
* @route '/quotation-management/billing-cancel-request'
*/
billingCancelRequest.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: billingCancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::billingCancelRequest
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:16
* @route '/quotation-management/billing-cancel-request'
*/
billingCancelRequest.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: billingCancelRequest.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::billingCancelRequest
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:16
* @route '/quotation-management/billing-cancel-request'
*/
const billingCancelRequestForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingCancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::billingCancelRequest
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:16
* @route '/quotation-management/billing-cancel-request'
*/
billingCancelRequestForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingCancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelRequestController::billingCancelRequest
* @see app/Http/Controllers/Billing/BillingCancelRequestController.php:16
* @route '/quotation-management/billing-cancel-request'
*/
billingCancelRequestForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingCancelRequest.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

billingCancelRequest.form = billingCancelRequestForm

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::billingCancelApproval
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:16
* @route '/quotation-management/billing-cancel-approval'
*/
export const billingCancelApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: billingCancelApproval.url(options),
    method: 'get',
})

billingCancelApproval.definition = {
    methods: ["get","head"],
    url: '/quotation-management/billing-cancel-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::billingCancelApproval
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:16
* @route '/quotation-management/billing-cancel-approval'
*/
billingCancelApproval.url = (options?: RouteQueryOptions) => {
    return billingCancelApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::billingCancelApproval
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:16
* @route '/quotation-management/billing-cancel-approval'
*/
billingCancelApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: billingCancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::billingCancelApproval
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:16
* @route '/quotation-management/billing-cancel-approval'
*/
billingCancelApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: billingCancelApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::billingCancelApproval
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:16
* @route '/quotation-management/billing-cancel-approval'
*/
const billingCancelApprovalForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingCancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::billingCancelApproval
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:16
* @route '/quotation-management/billing-cancel-approval'
*/
billingCancelApprovalForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingCancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::billingCancelApproval
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:16
* @route '/quotation-management/billing-cancel-approval'
*/
billingCancelApprovalForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: billingCancelApproval.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

billingCancelApproval.form = billingCancelApprovalForm

const quotationManagement = {
    quoteRequest: Object.assign(quoteRequest, quoteRequestF3c656),
    vendorSelection: Object.assign(vendorSelection, vendorSelection5181af),
    managerApproval: Object.assign(managerApproval, managerApproval3ec22d),
    cancelRequest: Object.assign(cancelRequest, cancelRequestD640cf),
    cancelApproval: Object.assign(cancelApproval, cancelApprovalB7fa4c),
    billingQuoteCreate: Object.assign(billingQuoteCreate, billingQuoteCreate082e7f),
    billingQuoteApproval: Object.assign(billingQuoteApproval, billingQuoteApprovalD9abaa),
    billingCancelRequest: Object.assign(billingCancelRequest, billingCancelRequest259496),
    billingCancelApproval: Object.assign(billingCancelApproval, billingCancelApprovalF8ce24),
    quotationMessages: Object.assign(quotationMessages, quotationMessages),
    commentAttachments: Object.assign(commentAttachments, commentAttachments),
}

export default quotationManagement