import AuthController from './AuthController'
import EstimateManagementController from './EstimateManagementController'

const Controllers = {
    AuthController: Object.assign(AuthController, AuthController),
    EstimateManagementController: Object.assign(EstimateManagementController, EstimateManagementController),
}

export default Controllers