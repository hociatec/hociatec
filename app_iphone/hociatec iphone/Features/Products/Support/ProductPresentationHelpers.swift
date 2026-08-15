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
    let dynamicAttributes = (product.attributes ?? [])
        .compactMap { nonEmptyValue($0.value) }

    let compactSpecs: [String]
    if dynamicAttributes.isEmpty {
        compactSpecs = [
            nonEmptyValue(product.brand),
            nonEmptyValue(product.memoryRam),
            (product.variantsCount ?? 1) > 1 ? nil : nonEmptyValue(product.storageCapacity),
            (product.variantsCount ?? 1) > 1 ? nil : nonEmptyValue(product.color)
        ].compactMap { $0 }
    } else {
        compactSpecs = Array(NSOrderedSet(array: dynamicAttributes)) as? [String] ?? dynamicAttributes
    }

    guard !compactSpecs.isEmpty else { return nil }
    return compactSpecs.joined(separator: " • ")
}

func productVariantSummaries(_ product: Product) -> [(label: String, values: String)] {
    let dynamicSummaries = (product.variantAttributes ?? []).compactMap { attribute -> (label: String, values: String)? in
        let values = attribute.values.compactMap { nonEmptyValue($0) }
        guard !values.isEmpty else { return nil }
        return (attribute.label, values.joined(separator: ", "))
    }

    if !dynamicSummaries.isEmpty {
        return dynamicSummaries
    }

    var legacy: [(label: String, values: String)] = []
    if let memoryRams = product.variantMemoryRams?.compactMap({ nonEmptyValue($0) }), !memoryRams.isEmpty {
        legacy.append(("RAM", memoryRams.joined(separator: ", ")))
    }
    if let colors = product.variantColors?.compactMap({ nonEmptyValue($0) }), !colors.isEmpty {
        legacy.append(("Coloris", colors.joined(separator: ", ")))
    }
    if let storages = product.variantStorages?.compactMap({ nonEmptyValue($0) }), !storages.isEmpty {
        legacy.append(("Stockages", storages.joined(separator: ", ")))
    }

    return legacy
}

func productPriceLabel(_ product: Product) -> String {
    let unitSuffix = nonEmptyValue(product.priceUnitLabel) ?? (product.sellingType == .rental ? "/mois" : "")
    return PriceFormatter.format(cents: product.effectivePriceCents) + unitSuffix
}

func facebookShareURL(for product: Product) -> URL {
    let target = AppConfig.websiteURL(path: "/catalogue/produits/\(product.slug)?mode=\(product.sellingType.rawValue)").absoluteString
    let encodedTarget = target.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? target
    return URL(string: "https://www.facebook.com/sharer/sharer.php?u=\(encodedTarget)")!
}

func emailShareURL(for product: Product) -> URL {
    let subject = "Découvrir \(product.name)"
    let productURL = AppConfig.websiteURL(path: "/catalogue/produits/\(product.slug)?mode=\(product.sellingType.rawValue)").absoluteString
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
