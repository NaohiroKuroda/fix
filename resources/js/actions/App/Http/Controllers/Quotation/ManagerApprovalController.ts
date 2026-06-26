import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::index
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:26
* @route '/estimate-management/manager-approval'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/estimate-management/manager-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::index
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:26
* @route '/estimate-management/manager-approval'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::index
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:26
* @route '/estimate-management/manager-approval'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::index
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:26
* @route '/estimate-management/manager-approval'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:41
* @route '/estimate-management/manager-approval'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/estimate-management/manager-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:41
* @route '/estimate-management/manager-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:41
* @route '/estimate-management/manager-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

const ManagerApprovalController = { index, confirm }

export default ManagerApprovalController