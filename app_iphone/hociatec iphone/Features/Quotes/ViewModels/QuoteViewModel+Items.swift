import Foundation

extension QuoteViewModel {
    func addServiceLine(_ service: QuoteService) {
        items.append(
            QuoteDraftItem(
                serviceId: service.id,
                productId: nil,
                quantity: 1,
                description: "",
                title: service.title,
                unitPriceCents: service.priceCents
            )
        )
    }

    func addProductLine(_ product: Product) {
        items.append(
            QuoteDraftItem(
                serviceId: nil,
                productId: product.id,
                quantity: 1,
                description: product.shortDescription,
                title: product.name,
                unitPriceCents: product.effectivePriceCents
            )
        )
    }

    func addCustomLine(title: String, unitPriceCents: Int, quantity: Int = 1, description: String = "") {
        let cleanTitle = title.trimmingCharacters(in: .whitespacesAndNewlines)
        items.append(
            QuoteDraftItem(
                serviceId: nil,
                productId: nil,
                quantity: max(1, quantity),
                description: description,
                title: cleanTitle.isEmpty ? "Ligne" : cleanTitle,
                unitPriceCents: max(0, unitPriceCents)
            )
        )
    }

    func removeLine(id: UUID) {
        items.removeAll { $0.id == id }
    }

    func buildValidItems() -> [QuoteRequestItem] {
        items.compactMap { draft -> QuoteRequestItem? in
            guard draft.quantity > 0 else { return nil }
            if let serviceId = draft.serviceId {
                return QuoteRequestItem(
                    serviceId: serviceId,
                    productId: nil,
                    quantity: draft.quantity,
                    description: draft.description.isEmpty ? nil : draft.description,
                    name: draft.title,
                    unitPriceCents: draft.unitPriceCents,
                    type: .service
                )
            }
            if let productId = draft.productId {
                return QuoteRequestItem(
                    serviceId: nil,
                    productId: productId,
                    quantity: draft.quantity,
                    description: draft.description.isEmpty ? nil : draft.description,
                    name: draft.title,
                    unitPriceCents: draft.unitPriceCents,
                    type: .product
                )
            }
            if draft.isCustom {
                let title = (draft.title ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
                guard !title.isEmpty else { return nil }
                guard let unitPriceCents = draft.unitPriceCents, unitPriceCents >= 0 else { return nil }
                return QuoteRequestItem(
                    serviceId: nil,
                    productId: nil,
                    quantity: draft.quantity,
                    description: draft.description.isEmpty ? nil : draft.description,
                    name: title,
                    unitPriceCents: unitPriceCents,
                    type: .custom
                )
            }
            return nil
        }
    }
}
