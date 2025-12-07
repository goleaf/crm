# Product Pricing & Inventory

## ADDED Requirements

#### Requirement 1: Products store cost and list pricing with currency and effective dating.
- Scenario: A pricing analyst sets Cost = $400 and List = $600 (USD) effective today, schedules an update to Cost = $420 next month, and the system surfaces the correct active prices in product detail, quotes, and APIs based on the effective window.

#### Requirement 2: Discount pricing rules support percentage/amount breaks, tiers, and date windows.
- Scenario: The analyst adds a “Q2 Volume Discount” that applies 10% off for quantities 10–49 and 15% off for 50+, valid from April 1 to June 30; when a quote adds 30 units, the rule calculates against the list price, expires automatically on July 1, and multiple active rules resolve to the best applicable price.

#### Requirement 3: Inventory tracking records availability and enforces status/lifecycle gating.
- Scenario: Receiving 100 units increases On Hand to 100; reserving 20 for an open order drops Available to 80 while logging a reservation; marking the product Inactive or End-of-Life blocks new reservations or shipments, while reactivating the product re-enables stock movements and availability calculations.
