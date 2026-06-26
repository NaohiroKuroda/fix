import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::index
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/estimate-management/cancel-approval'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/estimate-management/cancel-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::index
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/estimate-management/cancel-approval'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::index
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/estimate-management/cancel-approval'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::index
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/estimate-management/cancel-approval'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:41
* @route '/estimate-management/cancel-approval'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/estimate-management/cancel-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:41
* @route '/estimate-management/cancel-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:41
* @route '/estimate-management/cancel-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

const CancelApprovalController = { index, confirm }

export default CancelApprovalController