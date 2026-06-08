import ExecutionBudgetController from './ExecutionBudgetController'
import EstimateDetailController from './EstimateDetailController'
import StatusManagementController from './StatusManagementController'

const Controllers = {
    ExecutionBudgetController: Object.assign(ExecutionBudgetController, ExecutionBudgetController),
    EstimateDetailController: Object.assign(EstimateDetailController, EstimateDetailController),
    StatusManagementController: Object.assign(StatusManagementController, StatusManagementController),
}

export default Controllers