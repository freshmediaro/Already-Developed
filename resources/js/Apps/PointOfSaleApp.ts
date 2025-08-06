// Point of Sale Application - Comprehensive POS system for retail operations
import { BaseApp } from './BaseApp';

export class PointOfSaleApp extends BaseApp {
  constructor() {
    super('point-of-sale', {
      id: 'point-of-sale',
      name: 'Point of Sale',
      icon: 'fas fa-cash-register',
      iconType: 'fontawesome',
      iconBackground: 'orange-icon',
      component: 'PointOfSaleApp',
      category: 'business',
      permissions: [],
      installed: true,
      system: true,
      teamScoped: false,
      version: '1.0.0'
    });
  }

  protected createContent(): string {
    return `
      <div class="pos-app">
        <div class="pos-layout">
          <div class="pos-main">
            <div class="pos-header">
              <h1>Point of Sale</h1>
              <div class="pos-actions">
                <button class="btn-secondary hold-sale">Hold Sale</button>
                <button class="btn-secondary recall-sale">Recall Sale</button>
                <button class="btn-danger void-sale">Void Sale</button>
              </div>
            </div>

            <div class="pos-content">
              <div class="product-search">
                <div class="search-bar">
                  <i class="fas fa-search"></i>
                  <input type="text" placeholder="Search products or scan barcode..." class="product-search-input">
                  <button class="barcode-btn" title="Scan Barcode">
                    <i class="fas fa-barcode"></i>
                  </button>
                </div>
              </div>

              <div class="product-grid">
                <div class="product-category-tabs">
                  <button class="category-tab active" data-category="all">All</button>
                  <button class="category-tab" data-category="electronics">Electronics</button>
                  <button class="category-tab" data-category="clothing">Clothing</button>
                  <button class="category-tab" data-category="books">Books</button>
                </div>

                <div class="products-list">
                  <div class="product-item" data-product-id="1">
                    <div class="product-image">
                      <img src="/img/placeholder-auto-theme.png" alt="Smartphone">
                    </div>
                    <div class="product-details">
                      <h4>Smartphone XY</h4>
                      <p class="product-price">$299.99</p>
                      <p class="product-stock">Stock: 45</p>
                    </div>
                  </div>

                  <div class="product-item" data-product-id="2">
                    <div class="product-image">
                      <img src="/img/placeholder-auto-theme.png" alt="T-Shirt">
                    </div>
                    <div class="product-details">
                      <h4>Cotton T-Shirt</h4>
                      <p class="product-price">$19.99</p>
                      <p class="product-stock">Stock: 3</p>
                    </div>
                  </div>

                  <div class="product-item" data-product-id="3">
                    <div class="product-image">
                      <img src="/img/placeholder-auto-theme.png" alt="Book">
                    </div>
                    <div class="product-details">
                      <h4>Programming Guide</h4>
                      <p class="product-price">$49.99</p>
                      <p class="product-stock">Stock: 12</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="pos-cart">
            <div class="cart-header">
              <h3>Current Sale</h3>
              <div class="customer-info">
                <button class="add-customer-btn">
                  <i class="fas fa-user-plus"></i> Add Customer
                </button>
              </div>
            </div>

            <div class="cart-items">
              <div class="cart-item">
                <div class="item-details">
                  <h4>Smartphone XY</h4>
                  <p class="item-price">$299.99</p>
                </div>
                <div class="item-controls">
                  <button class="qty-btn decrease">-</button>
                  <span class="quantity">1</span>
                  <button class="qty-btn increase">+</button>
                  <button class="remove-item" title="Remove">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
                <div class="item-total">$299.99</div>
              </div>

              <div class="cart-item">
                <div class="item-details">
                  <h4>Cotton T-Shirt</h4>
                  <p class="item-price">$19.99</p>
                </div>
                <div class="item-controls">
                  <button class="qty-btn decrease">-</button>
                  <span class="quantity">2</span>
                  <button class="qty-btn increase">+</button>
                  <button class="remove-item" title="Remove">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
                <div class="item-total">$39.98</div>
              </div>
            </div>

            <div class="cart-summary">
              <div class="summary-row">
                <span>Subtotal:</span>
                <span>$339.97</span>
              </div>
              <div class="summary-row">
                <span>Tax (8.5%):</span>
                <span>$28.90</span>
              </div>
              <div class="summary-row discount">
                <span>Discount:</span>
                <span>-$0.00</span>
                <button class="apply-discount-btn">Apply</button>
              </div>
              <div class="summary-row total">
                <span>Total:</span>
                <span>$368.87</span>
              </div>
            </div>

            <div class="payment-section">
              <div class="payment-methods">
                <button class="payment-btn cash-btn active">
                  <i class="fas fa-money-bill"></i>
                  Cash
                </button>
                <button class="payment-btn card-btn">
                  <i class="fas fa-credit-card"></i>
                  Card
                </button>
                <button class="payment-btn digital-btn">
                  <i class="fas fa-mobile-alt"></i>
                  Digital
                </button>
              </div>

              <div class="cash-payment">
                <div class="cash-input-group">
                  <label>Amount Received:</label>
                  <input type="number" class="cash-amount" placeholder="0.00" step="0.01">
                </div>
                <div class="quick-amounts">
                  <button class="quick-amount-btn" data-amount="368.87">Exact</button>
                  <button class="quick-amount-btn" data-amount="370">$370</button>
                  <button class="quick-amount-btn" data-amount="380">$380</button>
                  <button class="quick-amount-btn" data-amount="400">$400</button>
                </div>
                <div class="change-due">
                  <span>Change Due: <strong>$0.00</strong></span>
                </div>
              </div>

              <div class="checkout-actions">
                <button class="btn-primary complete-sale">
                  <i class="fas fa-check"></i> Complete Sale
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  protected setupEventListeners(): void {
    super.setupEventListeners();
    
    const container = this.context?.contentElement;
    if (!container) return;

    container.addEventListener('click', (e: Event) => {
      const target = e.target as HTMLElement;
      
      if (target.closest('.product-item')) {
        this.addProductToCart(target);
      } else if (target.closest('.qty-btn.increase')) {
        this.increaseQuantity(target);
      } else if (target.closest('.qty-btn.decrease')) {
        this.decreaseQuantity(target);
      } else if (target.closest('.remove-item')) {
        this.removeItem(target);
      } else if (target.closest('.payment-btn')) {
        this.selectPaymentMethod(target);
      } else if (target.closest('.quick-amount-btn')) {
        this.setQuickAmount(target);
      } else if (target.closest('.complete-sale')) {
        this.completeSale();
      } else if (target.closest('.hold-sale')) {
        this.holdSale();
      } else if (target.closest('.void-sale')) {
        this.voidSale();
      }
    });

    // Cash amount input
    const cashInput = container.querySelector('.cash-amount') as HTMLInputElement;
    if (cashInput) {
      cashInput.addEventListener('input', () => {
        this.calculateChange();
      });
    }
  }

  private addProductToCart(target: HTMLElement): void {
    const productItem = target.closest('.product-item');
    if (productItem) {
      const productId = productItem.getAttribute('data-product-id');
      console.log('Adding product to cart:', productId);
      // Implementation for adding product to cart
    }
  }

  private increaseQuantity(target: HTMLElement): void {
    const cartItem = target.closest('.cart-item') as HTMLElement;
    if (cartItem) {
      const quantitySpan = cartItem.querySelector('.quantity');
      if (quantitySpan) {
        const currentQty = parseInt(quantitySpan.textContent || '0');
        quantitySpan.textContent = (currentQty + 1).toString();
        this.updateItemTotal(cartItem);
      }
    }
  }

  private decreaseQuantity(target: HTMLElement): void {
    const cartItem = target.closest('.cart-item') as HTMLElement;
    if (cartItem) {
      const quantitySpan = cartItem.querySelector('.quantity');
      if (quantitySpan) {
        const currentQty = parseInt(quantitySpan.textContent || '0');
        if (currentQty > 1) {
          quantitySpan.textContent = (currentQty - 1).toString();
          this.updateItemTotal(cartItem);
        }
      }
    }
  }

  private removeItem(target: HTMLElement): void {
    const cartItem = target.closest('.cart-item');
    if (cartItem) {
      cartItem.remove();
      this.updateCartSummary();
    }
  }

  private selectPaymentMethod(target: HTMLElement): void {
    const container = this.context?.contentElement;
    if (container) {
      container.querySelectorAll('.payment-btn').forEach((btn: any) => btn.classList.remove('active'));
      target.classList.add('active');
    }
  }

  private setQuickAmount(target: HTMLElement): void {
    const amount = target.getAttribute('data-amount');
    if (amount) {
      const cashInput = this.context?.contentElement?.querySelector('.cash-amount') as HTMLInputElement;
      if (cashInput) {
        cashInput.value = amount;
        this.calculateChange();
      }
    }
  }

  private calculateChange(): void {
    const container = this.context?.contentElement;
    if (!container) return;

    const cashInput = container.querySelector('.cash-amount') as HTMLInputElement;
    const changeDue = container.querySelector('.change-due strong');
    
    if (cashInput && changeDue) {
      const received = parseFloat(cashInput.value) || 0;
      const total = 368.87; // This would be calculated dynamically
      const change = received - total;
      
      changeDue.textContent = `$${change.toFixed(2)}`;
    }
  }

  private updateItemTotal(cartItem: HTMLElement): void {
    const priceElement = cartItem.querySelector('.item-price');
    const quantityElement = cartItem.querySelector('.quantity');
    const totalElement = cartItem.querySelector('.item-total');
    
    if (priceElement && quantityElement && totalElement) {
      const price = parseFloat(priceElement.textContent?.replace('$', '') || '0');
      const quantity = parseInt(quantityElement.textContent || '0');
      const total = price * quantity;
      
      totalElement.textContent = `$${total.toFixed(2)}`;
    }
    
    this.updateCartSummary();
  }

  private updateCartSummary(): void {
    // Implementation for updating cart summary
    console.log('Updating cart summary');
  }

  private completeSale(): void {
    console.log('Completing sale');
    // Implementation for completing the sale
  }

  private holdSale(): void {
    console.log('Holding sale');
    // Implementation for holding the sale
  }

  private voidSale(): void {
    console.log('Voiding sale');
    // Implementation for voiding the sale
  }
}

export default PointOfSaleApp;