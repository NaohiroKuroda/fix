import AuthController from './AuthController'
import Quotation from './Quotation'

const Controllers = {
    AuthController: Object.assign(AuthController, AuthController),
    Quotation: Object.assign(Quotation, Quotation),
}

export default Controllers