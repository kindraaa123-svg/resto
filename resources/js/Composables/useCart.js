import { reactive, computed, watch } from 'vue'

const CART_STORAGE_KEY = 'pos_customer_cart'

// Initialize state from localStorage if available
const savedCart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || '{"items": []}')

const state = reactive({
    items: savedCart.items || [], // { menu: Object, quantity: Number, addons: Array, totalPrice: Number }
})

// Watch for changes and persist to localStorage
watch(() => state.items, (newItems) => {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify({ items: newItems }))
}, { deep: true })

export function useCart() {
    const items = computed(() => state.items)
    
    const totalItems = computed(() => {
        return state.items.reduce((sum, item) => sum + item.quantity, 0)
    })
    
    const totalPrice = computed(() => {
        return state.items.reduce((sum, item) => sum + item.totalPrice, 0)
    })

    const addToCart = (menu, quantity, addons = [], itemTotal) => {
        // Find existing item with same menu ID and identical addons
        const existingItemIndex = state.items.findIndex(item => {
            // Check if both are either packages or standard menus
            if (item.menu.is_package !== menu.is_package) return false;
            
            // Basic ID check
            if (item.menu.id !== menu.id) return false;

            // For standard menus, check addons
            if (!menu.is_package) {
                if (item.addons.length !== addons.length) return false;
                
                const existingAddonIds = item.addons.map(a => a.addonid).sort().join(',');
                const newAddonIds = addons.map(a => a.addonid).sort().join(',');
                
                return existingAddonIds === newAddonIds;
            }

            // For packages, the ID (pseudo-ID) is enough as they don't have user-selectable addons yet
            return true;
        });

        if (existingItemIndex !== -1) {
            // Update existing item
            const item = state.items[existingItemIndex];
            const pricePerPortion = item.totalPrice / item.quantity;
            item.quantity += quantity;
            item.totalPrice = pricePerPortion * item.quantity;
        } else {
            // Add new item
            state.items.push({
                menu,
                quantity,
                addons,
                totalPrice: itemTotal,
                // Mirror properties to top level for robust backend parsing
                is_package: !!menu.is_package,
                packageid: menu.packageid || null,
                menus: menu.menus || [],
                all_packageids: menu.all_packageids || []
            });
        }
    }

    const updateQuantity = (index, newQuantity) => {
        if (newQuantity <= 0) {
            removeFromCart(index)
            return
        }
        
        const item = state.items[index]
        const pricePerPortion = item.totalPrice / item.quantity
        item.quantity = newQuantity
        item.totalPrice = pricePerPortion * newQuantity
    }

    const removeFromCart = (index) => {
        state.items.splice(index, 1)
    }

    const clearCart = () => {
        state.items = []
        localStorage.removeItem(CART_STORAGE_KEY)
    }

    return {
        items,
        totalItems,
        totalPrice,
        addToCart,
        updateQuantity,
        removeFromCart,
        clearCart
    }
}
