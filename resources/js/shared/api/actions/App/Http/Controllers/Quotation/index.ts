import Payable from './Payable'
import Billing from './Billing'

const Quotation = {
    Payable: Object.assign(Payable, Payable),
    Billing: Object.assign(Billing, Billing),
}

export default Quotation