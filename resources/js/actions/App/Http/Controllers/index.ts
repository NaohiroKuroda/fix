import AuthController from './AuthController'
import ExecutionBudgetController from './ExecutionBudgetController'
import EstimateDetailController from './EstimateDetailController'
import StatusManagementController from './StatusManagementController'
import EstimateManagementController from './EstimateManagementController'

const Controllers = {
    AuthController: Object.assign(AuthController, AuthController),
    ExecutionBudgetController: Object.assign(ExecutionBudgetController, ExecutionBudgetController),
    EstimateDetailController: Object.assign(EstimateDetailController, EstimateDetailController),
    StatusManagementController: Object.assign(StatusManagementController, StatusManagementController),
    EstimateManagementController: Object.assign(EstimateManagementController, EstimateManagementController),
}

export default Controllers