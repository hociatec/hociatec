import Foundation

final class AppServices {
    let assets: AssetServing
    let account: AccountServing
    let cart: CartServing
    let products: ProductServing
    let favorites: FavoritesServing
    let orders: OrderServing
    let appointments: AppointmentServing
    let quotes: QuoteServing
    let news: NewsServing
    let serviceCatalog: ServiceCatalogServing
    let training: TrainingServing
    let workspace: WorkspaceServing
    let support: SupportServing
    let vouchers: VoucherServing
    let audits: AuditServing
    let tradeIn: TradeInServing
    let beta: BetaServing
    let contact: ContactServing

    init(apiClient: APIClient) {
        let account = AccountService(api: apiClient)
        let cart = CartService(api: apiClient)
        let products = ProductService(api: apiClient)
        let favorites = FavoritesService(api: apiClient)
        let orders = OrderService(api: apiClient)
        let appointments = AppointmentService(api: apiClient)
        let quotes = QuoteServiceLayer(api: apiClient)
        let news = NewsService(api: apiClient)
        let serviceCatalog = ServiceCatalogService(api: apiClient)
        let training = TrainingService(api: apiClient)
        let workspace = WorkspaceService(api: apiClient)
        let support = SupportService(api: apiClient)
        let vouchers = VoucherService(api: apiClient)
        let audits = AuditService(api: apiClient)
        let tradeIn = TradeInService(api: apiClient)
        let beta = BetaService(api: apiClient)
        let contact = ContactService(api: apiClient)

        self.assets = products
        self.account = account
        self.cart = cart
        self.products = products
        self.favorites = favorites
        self.orders = orders
        self.appointments = appointments
        self.quotes = quotes
        self.news = news
        self.serviceCatalog = serviceCatalog
        self.training = training
        self.workspace = workspace
        self.support = support
        self.vouchers = vouchers
        self.audits = audits
        self.tradeIn = tradeIn
        self.beta = beta
        self.contact = contact
    }
}
