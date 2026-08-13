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

    func addToCart(productId: Int, quantity: Int, rentalMonths: Int? = nil) async throws -> Cart {
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

        let data: CartData = try await request(
            path: "api/public/cart/items",
            method: "POST",
            body: body,
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func updateCart(productId: Int, quantity: Int, rentalMonths: Int? = nil, currentRentalMonths: Int? = nil) async throws -> Cart {
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

        let data: CartData = try await request(
            path: "api/public/cart/items/\(productId)",
            method: "PATCH",
            body: body,
            authorized: false,
            attachCartToken: true
        )
        return data.cart
    }

    func removeFromCart(productId: Int) async throws -> Cart {
        let data: CartData = try await request(
            path: "api/public/cart/items/\(productId)",
            method: "DELETE",
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
