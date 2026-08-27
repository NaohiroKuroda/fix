import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
export const execute = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: execute.url(options),
    method: 'post',
})

execute.definition = {
    methods: ["post"],
    url: '/order-delivery/order-execution',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
execute.url = (options?: RouteQueryOptions) => {
    return execute.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
execute.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: execute.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
const executeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: execute.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
executeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: execute.url(options),
    method: 'post',
})

execute.form = executeForm

const orderExecution = {
    execute: Object.assign(execute, execute),
}

export default orderExecution