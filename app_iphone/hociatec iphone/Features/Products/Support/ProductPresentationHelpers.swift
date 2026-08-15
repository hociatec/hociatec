import Foundation

func productSellingContext(_ product: Product) -> String {
    let sellingTypeLabel = product.sellingTypeLabel ?? {
        switch product.sellingType {
        case .rental: return "Location"
        case .sale: return "Vente"
        case .unknown: return "Produit"
        }
    }()
    return "\(product.category.name) (\(sellingTypeLabel))"
}

func productConfiguration(_ product: Product) -> String? {
    let compactSpecs = [
        nonEmptyValue(product.brand),
        nonEmptyValue(product.memoryRam),
        (product.variantsCount ?? 1) > 1 ? nil : nonEmptyValue(product.storageCapacity),
        (product.variantsCount ?? 1) > 1 ? nil : nonEmptyValue(product.color)
    ].compactMap { $0 }

    guard !compactSpecs.isEmpty else { return nil }
    return compactSpecs.joined(separator: " • ")
}

func productPriceLabel(_ product: Product) -> String {
    let unitSuffix = nonEmptyValue(product.priceUnitLabel) ?? (product.sellingType == .rental ? "/mois" : "")
    return PriceFormatter.format(cents: product.effectivePriceCents) + unitSuffix
}

func facebookShareURL(for product: Product) -> URL {
    let target = AppConfig.websiteURL(path: "/catalogue/produits/\(product.slug)").absoluteString
    let encodedTarget = target.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? target
    return URL(string: "https://www.facebook.com/sharer/sharer.php?u=\(encodedTarget)")!
}

func emailShareURL(for product: Product) -> URL {
    let subject = "Découvrir \(product.name)"
    let productURL = AppConfig.websiteURL(path: "/catalogue/produits/\(product.slug)").absoluteString
    let body = "\(product.name)\n\(productPriceLabel(product))\n\(productURL)"
    let encodedSubject = subject.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? subject
    let encodedBody = body.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? body
    return URL(string: "mailto:?subject=\(encodedSubject)&body=\(encodedBody)")!
}

func nonEmptyValue(_ value: String?) -> String? {
    guard let trimmed = value?.trimmingCharacters(in: .whitespacesAndNewlines), !trimmed.isEmpty else {
        return nil
    }
    return trimmed
}
