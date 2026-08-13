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
    let tradeIn: TradeInServing
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
        let tradeIn = TradeInService(api: apiClient)
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
        self.tradeIn = tradeIn
        self.contact = contact
    }
}
