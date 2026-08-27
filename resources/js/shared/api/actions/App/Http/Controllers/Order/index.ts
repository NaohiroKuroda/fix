import Payable from './Payable'
import Billing from './Billing'

const Order = {
    Payable: Object.assign(Payable, Payable),
    Billing: Object.assign(Billing, Billing),
}

export default Order