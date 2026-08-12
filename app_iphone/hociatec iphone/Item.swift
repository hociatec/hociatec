//
//  Item.swift
//  hociatec iphone
//
//  Created by Hocine Sahraoui on 06/12/2025.
//

import Foundation
import SwiftData

@Model
final class Item {
    var timestamp: Date
    
    init(timestamp: Date) {
        self.timestamp = timestamp
    }
}
