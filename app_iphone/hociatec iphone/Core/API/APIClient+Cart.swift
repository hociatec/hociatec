import Foundation

extension APIClient {
    func fetchCart() async throws -> Cart {
        let data: CartData = try await request(
            path: "api/public/cart",
            method: "GET",
            query: nil,
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func addToCart(
        productId: Int,
        quantity: Int,
        rentalMonths: Int? = nil,
        rentalStartDate: String? = nil
    ) async throws -> Cart {
        var body: [String: Any] = [
            "productId": productId,
            "quantity": max(1, quantity)
        ]
        if let token = sessionStore.cartToken {
            body["cartToken"] = token
        }
        if let rentalMonths {
            body["rentalMonths"] = rentalMonths
        }
        if let rentalStartDate {
            body["rentalStartDate"] = rentalStartDate
        }

        let data: CartData = try await request(
            path: "api/public/cart/items",
            method: "POST",
            body: body,
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func updateCart(
        productId: Int,
        quantity: Int,
        rentalMonths: Int? = nil,
        currentRentalMonths: Int? = nil,
        rentalStartDate: String? = nil,
        currentRentalStartDate: String? = nil
    ) async throws -> Cart {
        var body: [String: Any] = [
            "quantity": max(0, quantity)
        ]
        if let token = sessionStore.cartToken {
            body["cartToken"] = token
        }
        if let rentalMonths {
            body["rentalMonths"] = rentalMonths
        }
        if let currentRentalMonths {
            body["currentRentalMonths"] = currentRentalMonths
        }
        if let rentalStartDate {
            body["rentalStartDate"] = rentalStartDate
        }
        if let currentRentalStartDate {
            body["currentRentalStartDate"] = currentRentalStartDate
        }

        let data: CartData = try await request(
            path: "api/public/cart/items/\(productId)",
            method: "PATCH",
            body: body,
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func removeFromCart(
        productId: Int,
        rentalMonths: Int? = nil,
        rentalStartDate: String? = nil
    ) async throws -> Cart {
        var query: [URLQueryItem] = []
        if let rentalMonths {
            query.append(URLQueryItem(name: "currentRentalMonths", value: String(rentalMonths)))
        }
        if let rentalStartDate {
            query.append(URLQueryItem(name: "currentRentalStartDate", value: rentalStartDate))
        }
        let data: CartData = try await request(
            path: "api/public/cart/items/\(productId)",
            method: "DELETE",
            query: query.isEmpty ? nil : query,
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func clearCart() async throws -> Cart {
        let data: CartData = try await request(
            path: "api/public/cart",
            method: "DELETE",
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }
}
