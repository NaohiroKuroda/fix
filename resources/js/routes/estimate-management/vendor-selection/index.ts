import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\EstimateManagementController::confirm
* @see app/Http/Controllers/EstimateManagementController.php:85
* @route '/estimate-management/vendor-selection'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/estimate-management/vendor-selection',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::confirm
* @see app/Http/Controllers/EstimateManagementController.php:85
* @route '/estimate-management/vendor-selection'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::confirm
* @see app/Http/Controllers/EstimateManagementController.php:85
* @route '/estimate-management/vendor-selection'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

const vendorSelection = {
    confirm: Object.assign(confirm, confirm),
}

export default vendorSelection