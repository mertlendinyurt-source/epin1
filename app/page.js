'use client'

import { useState, useEffect } from 'react'
import { User, Check, X, Loader2, Info, Menu } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Toaster } from '@/components/ui/sonner'
import { toast } from 'sonner'

export default function App() {
  const [products, setProducts] = useState([])
  const [loading, setLoading] = useState(true)
  const [selectedProduct, setSelectedProduct] = useState(null)
  const [checkoutOpen, setCheckoutOpen] = useState(false)
  const [playerId, setPlayerId] = useState('')
  const [playerName, setPlayerName] = useState('')
  const [playerLoading, setPlayerLoading] = useState(false)
  const [playerValid, setPlayerValid] = useState(null)
  const [orderProcessing, setOrderProcessing] = useState(false)

  useEffect(() => {
    fetchProducts()
  }, [])

  const fetchProducts = async () => {
    try {
      const response = await fetch('/api/products')
      const data = await response.json()
      if (data.success) {
        setProducts(data.data)
      }
    } catch (error) {
      console.error('Error fetching products:', error)
      toast.error('Ürünler yüklenirken hata oluştu')
    } finally {
      setLoading(false)
    }
  }

  const handleProductSelect = (product) => {
    setSelectedProduct(product)
    setCheckoutOpen(true)
    setPlayerId('')
    setPlayerName('')
    setPlayerValid(null)
  }

  const resolvePlayerName = async (id) => {
    if (!id || id.length < 6) {
      setPlayerValid(false)
      setPlayerName('')
      return
    }

    setPlayerLoading(true)
    try {
      const response = await fetch(`/api/player/resolve?id=${id}`)
      const data = await response.json()
      
      if (data.success) {
        setPlayerName(data.data.playerName)
        setPlayerValid(true)
        toast.success('Oyuncu bulundu!')
      } else {
        setPlayerName('')
        setPlayerValid(false)
        toast.error(data.error || 'Oyuncu bulunamadı')
      }
    } catch (error) {
      console.error('Error resolving player:', error)
      setPlayerName('')
      setPlayerValid(false)
      toast.error('Oyuncu adı alınırken hata oluştu')
    } finally {
      setPlayerLoading(false)
    }
  }

  useEffect(() => {
    if (playerId) {
      const timer = setTimeout(() => {
        resolvePlayerName(playerId)
      }, 600)
      return () => clearTimeout(timer)
    } else {
      setPlayerName('')
      setPlayerValid(null)
    }
  }, [playerId])

  const handleCheckout = async () => {
    if (!playerValid || !playerName) {
      toast.error('Lütfen geçerli bir Oyuncu ID girin')
      return
    }

    setOrderProcessing(true)
    try {
      const response = await fetch('/api/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          productId: selectedProduct.id,
          playerId,
          playerName
        })
      })

      const data = await response.json()
      
      if (data.success) {
        window.location.href = data.data.paymentUrl
      } else {
        toast.error(data.error || 'Sipariş oluşturulamadı')
      }
    } catch (error) {
      console.error('Error creating order:', error)
      toast.error('Sipariş oluşturulurken hata oluştu')
    } finally {
      setOrderProcessing(false)
    }
  }

  // Filter Sidebar Component
  const FilterSidebar = () => (
    <div className="w-full rounded-lg bg-[#1e2229] p-5">
      <div className="flex items-center gap-2 mb-5">
        <svg className="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
          <path d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" />
        </svg>
        <span className="text-white font-bold text-base uppercase">Filtrele</span>
      </div>

      <div className="space-y-5">
        <div>
          <h3 className="text-white text-sm font-bold mb-3">Bölge</h3>
          <div className="mb-2">
            <input 
              type="text" 
              placeholder="Ara"
              className="w-full px-3 py-1.5 text-sm bg-black/30 border border-white/10 rounded text-white placeholder:text-white/40"
            />
          </div>
          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm text-white cursor-pointer hover:text-white/80">
              <input type="checkbox" className="w-3.5 h-3.5 rounded" defaultChecked />
              <span>🇹🇷</span>
              <span>Türkiye</span>
            </label>
            <label className="flex items-center gap-2 text-sm text-white cursor-pointer hover:text-white/80">
              <input type="checkbox" className="w-3.5 h-3.5 rounded" />
              <span>🌍</span>
              <span>Küresel</span>
            </label>
            <label className="flex items-center gap-2 text-sm text-white cursor-pointer hover:text-white/80">
              <input type="checkbox" className="w-3.5 h-3.5 rounded" />
              <span>🇩🇪</span>
              <span>Almanya</span>
            </label>
            <label className="flex items-center gap-2 text-sm text-white cursor-pointer hover:text-white/80">
              <input type="checkbox" className="w-3.5 h-3.5 rounded" />
              <span>🇫🇷</span>
              <span>Fransa</span>
            </label>
            <label className="flex items-center gap-2 text-sm text-white cursor-pointer hover:text-white/80">
              <input type="checkbox" className="w-3.5 h-3.5 rounded" />
              <span>🇯🇵</span>
              <span>Japonya</span>
            </label>
          </div>
        </div>

        <div className="pt-3 border-t border-white/10">
          <h3 className="text-white text-sm font-bold mb-3">Fiyat Aralığı</h3>
          <div className="flex gap-2">
            <input 
              type="number" 
              placeholder="En Az"
              className="w-full px-2 py-1.5 text-xs bg-black/30 border border-white/10 rounded text-white placeholder:text-white/40"
            />
            <input 
              type="number" 
              placeholder="En Çok"
              className="w-full px-2 py-1.5 text-xs bg-black/30 border border-white/10 rounded text-white placeholder:text-white/40"
            />
          </div>
        </div>

        <div className="pt-3 border-t border-white/10">
          <h3 className="text-white text-sm font-bold mb-3">Kelime ile Filtrele</h3>
          <input 
            type="text" 
            placeholder="Kelime"
            className="w-full px-3 py-1.5 text-sm bg-black/30 border border-white/10 rounded text-white placeholder:text-white/40"
          />
        </div>

        <Button className="w-full h-10 bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm rounded-full">
          Filtreleri Uygula
        </Button>
      </div>
    </div>
  )

  return (
    <div className="min-h-screen bg-[#1a1a1a]">
      <Toaster position="top-center" richColors />
      
      {/* Header - 60px */}
      <header className="h-[60px] bg-[#1a1a1a] border-b border-white/5">
        <div className="h-full max-w-[1920px] mx-auto px-4 md:px-6 flex items-center justify-between">
          <div className="flex items-center gap-2 md:gap-3">
            <div className="w-8 h-8 md:w-9 md:h-9 rounded bg-blue-600 flex items-center justify-center font-black text-xs md:text-sm text-white">
              UC
            </div>
            <span className="text-white font-semibold text-base md:text-lg">PUBG UC</span>
          </div>
            
          <div className="flex items-center gap-2 md:gap-4">
            <Sheet>
              <SheetTrigger asChild>
                <Button 
                  variant="ghost" 
                  size="icon" 
                  className="md:hidden text-white/60 hover:text-white hover:bg-white/10"
                >
                  <Menu className="w-5 h-5" />
                </Button>
              </SheetTrigger>
              <SheetContent side="left" className="w-[280px] bg-[#1e2229] border-white/10 p-0">
                <div className="p-5">
                  <FilterSidebar />
                </div>
              </SheetContent>
            </Sheet>
            
            <Button 
              variant="ghost" 
              size="icon" 
              className="text-white/60 hover:text-white hover:bg-white/10"
              onClick={() => window.location.href = '/admin/login'}
            >
              <User className="w-5 h-5" />
            </Button>
          </div>
        </div>
      </header>

      {/* Hero - 300px on desktop, shorter on mobile */}
      <div className="relative h-[200px] md:h-[300px] flex items-start overflow-hidden bg-[#1a1a1a]">
        <div 
          className="absolute inset-0 bg-cover bg-center"
          style={{
            backgroundImage: 'url(https://images.pexels.com/photos/5380620/pexels-photo-5380620.jpeg?auto=compress&cs=tinysrgb&w=1920)'
          }}
        />
        <div className="absolute inset-0 bg-gradient-to-b from-black/50 via-black/60 to-[#1a1a1a]" />
        
        <div className="relative z-10 max-w-[1920px] w-full mx-auto px-4 md:px-6 pt-6 md:pt-10">
          <div className="flex items-center gap-3 md:gap-4">
            <div className="w-14 h-14 md:w-20 md:h-20 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center shadow-lg">
              <span className="font-black text-xl md:text-3xl text-white">P</span>
            </div>
            <div>
              <div className="text-xs md:text-sm text-white/60 mb-0.5 md:mb-1">Anasayfa &gt; Oyunlar</div>
              <h1 className="text-xl md:text-[28px] font-bold text-white">PUBG Mobile</h1>
              <div className="flex items-center gap-1.5 md:gap-2 mt-0.5 md:mt-1">
                <span className="text-yellow-400 text-xs md:text-sm">★★★★★ 5/5</span>
                <span className="text-white/70 text-xs md:text-sm">(2008) yorum</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-[1920px] mx-auto px-4 md:px-6 py-4 md:py-6">
        <div className="flex gap-4 md:gap-5">
          {/* Desktop Sidebar - Hidden on mobile */}
          <div className="hidden lg:block w-[240px] xl:w-[265px] flex-shrink-0">
            <div className="sticky top-24">
              <FilterSidebar />
            </div>
          </div>

          {/* Products Grid */}
          <div className="flex-1">
            {loading ? (
              <div className="flex items-center justify-center py-20">
                <Loader2 className="w-8 h-8 text-blue-500 animate-spin" />
              </div>
            ) : (
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4">
                {products.map((product) => (
                  <div
                    key={product.id}
                    onClick={() => handleProductSelect(product)}
                    className="group relative rounded overflow-hidden cursor-pointer transition-all hover:shadow-lg"
                    style={{ backgroundColor: '#252525' }}
                  >
                    {/* Info Icon */}
                    <div className="absolute top-2 right-2 w-4 h-4 rounded-full bg-white/20 flex items-center justify-center z-10">
                      <Info className="w-2.5 h-2.5 text-white" />
                    </div>

                    {/* UC Image - Much smaller */}
                    <div className="relative h-28 md:h-32 overflow-hidden flex items-center justify-center bg-gradient-to-br from-zinc-900/30 to-zinc-950/30">
                      <img 
                        src="https://images.unsplash.com/photo-1645690364326-1f80098eca66?w=150&h=150&fit=crop"
                        alt="UC"
                        className="w-16 h-16 md:w-20 md:h-20 object-contain opacity-85"
                      />
                    </div>

                    {/* Content - Minimal padding */}
                    <div className="p-3">
                      {/* MOBILE */}
                      <div className="text-[11px] text-white/70 font-bold uppercase mb-1">MOBILE</div>
                      
                      {/* UC Amount */}
                      <div className="text-base md:text-[18px] font-bold text-white mb-2">
                        {product.ucAmount} UC
                      </div>

                      {/* Region */}
                      <div className="flex items-center gap-1 text-[11px] md:text-xs font-bold text-white mb-0.5">
                        <span>🇹🇷 TÜRKİYE</span>
                      </div>
                      
                      {/* Availability */}
                      <div className="text-[10px] text-[#32CD32] mb-2">Bölgenizde kullanılabilir</div>

                      {/* Prices */}
                      <div>
                        {product.discountPrice < product.price && (
                          <div className="text-[12px] md:text-[13px] text-[#B22222] line-through mb-0.5">
                            ₺ {product.price.toFixed(2)}
                          </div>
                        )}
                        
                        <div className="text-base md:text-[18px] font-bold text-white mb-0.5">
                          ₺ {product.discountPrice.toFixed(2)}
                        </div>
                        
                        {product.discountPercent > 0 && (
                          <div className="text-[10px] md:text-[11px] text-[#32CD32]">
                            {product.discountPercent}% indirim
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Checkout Dialog */}
      <Dialog open={checkoutOpen} onOpenChange={setCheckoutOpen}>
        <DialogContent className="max-w-[95vw] md:max-w-3xl p-0 gap-0 overflow-hidden bg-[#1F232A] border border-white/10">
          <DialogHeader className="px-4 md:px-6 py-4 md:py-5 border-b border-white/5">
            <DialogTitle className="text-base md:text-lg font-bold text-white uppercase tracking-wide">ÖDEME TÜRÜNÜ SEÇİN</DialogTitle>
          </DialogHeader>
          
          <div className="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-white/5">
            {/* Left: Player Info & Payment Methods */}
            <div className="p-4 md:p-6 space-y-4 md:space-y-6">
              <div>
                <Label className="text-xs text-white/60 mb-2 block uppercase tracking-wide">Oyuncu ID</Label>
                <div className="relative">
                  <Input
                    placeholder="Oyuncu ID Girin"
                    value={playerId}
                    onChange={(e) => setPlayerId(e.target.value)}
                    className="h-10 md:h-11 px-3 md:px-4 text-sm bg-[#12161D] text-white placeholder:text-white/30 border-white/10 focus:border-blue-500"
                  />
                  {playerLoading && (
                    <Loader2 className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 animate-spin text-blue-500" />
                  )}
                  {!playerLoading && playerValid === true && (
                    <Check className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 md:w-5 h-4 md:h-5 text-green-500" />
                  )}
                  {!playerLoading && playerValid === false && (
                    <X className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 md:w-5 h-4 md:h-5 text-red-500" />
                  )}
                </div>
              </div>

              {playerName && (
                <div className="px-3 md:px-4 py-2.5 md:py-3 rounded bg-green-500/15 border border-green-500/30">
                  <div className="flex items-center gap-2 text-green-400 mb-1 text-xs font-semibold">
                    <Check className="w-3.5 md:w-4 h-3.5 md:h-4" />
                    <span>Oyuncu Bulundu</span>
                  </div>
                  <p className="text-white text-sm font-bold">{playerName}</p>
                </div>
              )}

              <div>
                <Label className="text-xs text-white/60 mb-3 block uppercase tracking-wide">Ödeme yöntemleri</Label>
                <div className="px-3 md:px-4 py-3 md:py-3.5 rounded-lg flex items-center justify-between cursor-pointer bg-[#12161D] border border-white/10">
                  <div className="flex items-center gap-2 md:gap-3">
                    <div className="w-8 h-6 md:w-10 md:h-8 rounded flex items-center justify-center bg-[#1F232A]">
                      <span className="text-lg md:text-xl">💳</span>
                    </div>
                    <div>
                      <div className="text-xs md:text-sm font-semibold text-white">Kredi / Banka Kartı</div>
                      <div className="text-[10px] md:text-xs text-white/50">Anında teslimat</div>
                    </div>
                  </div>
                  <div className="w-4 h-4 md:w-5 md:h-5 rounded-full bg-green-500 flex items-center justify-center">
                    <Check className="w-2.5 md:w-3 h-2.5 md:h-3 text-white" />
                  </div>
                </div>
              </div>
            </div>

            {/* Right: Order Summary */}
            {selectedProduct && (
              <div className="p-4 md:p-6 space-y-4 md:space-y-5">
                <div>
                  <Label className="text-xs text-white/60 mb-3 block uppercase tracking-wide">Ürün</Label>
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 md:w-14 md:h-14 rounded flex items-center justify-center bg-[#12161D]">
                      <img 
                        src="https://images.unsplash.com/photo-1645690364326-1f80098eca66?w=100&h=100&fit=crop"
                        alt="UC"
                        className="w-8 h-8 md:w-10 md:h-10 object-contain opacity-70"
                      />
                    </div>
                    <div>
                      <div className="text-sm font-bold text-white">{selectedProduct.title}</div>
                      <div className="text-xs text-white/50 flex items-center gap-1.5">
                        🇹🇷 TÜRKİYE
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <Label className="text-xs text-white/60 mb-3 block uppercase tracking-wide">Fiyat detayları</Label>
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span className="text-white/60">Orjinal Fiyat</span>
                      <span className="text-white/80">₺ {selectedProduct.price.toFixed(2)}</span>
                    </div>
                    {selectedProduct.discountPrice < selectedProduct.price && (
                      <div className="flex justify-between text-sm">
                        <span className="text-green-400 font-semibold">Size Özel Fiyat</span>
                        <span className="text-green-400 font-semibold">₺ {selectedProduct.discountPrice.toFixed(2)}</span>
                      </div>
                    )}
                  </div>
                </div>

                <div className="pt-3 md:pt-4 border-t border-white/5">
                  <div className="flex justify-between items-baseline mb-4 md:mb-5">
                    <span className="text-xs md:text-sm text-white/60 uppercase tracking-wide">Ödenecek Tutar</span>
                    <span className="text-xl md:text-2xl font-black text-green-400">
                      ₺ {selectedProduct.discountPrice.toFixed(2)}
                    </span>
                  </div>

                  <Button
                    onClick={handleCheckout}
                    disabled={!playerValid || orderProcessing}
                    className="w-full h-11 md:h-12 bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm uppercase tracking-wide rounded-lg"
                  >
                    {orderProcessing ? (
                      <>
                        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                        İşleniyor...
                      </>
                    ) : (
                      'Ödemeye Git'
                    )}
                  </Button>
                </div>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>

      {/* Footer */}
      <footer className="mt-12 md:mt-16 py-6 md:py-8 bg-[#12151a] border-t border-white/5">
        <div className="max-w-[1920px] mx-auto px-4 md:px-6">
          <div className="text-center text-white/30 text-xs">
            <p>© 2024 PUBG UC Store. Tüm hakları saklıdır.</p>
            <p className="mt-2 text-white/20 text-[11px]">
              Bu site PUBG Mobile ile resmi bir bağlantısı yoktur.
            </p>
          </div>
        </div>
      </footer>
    </div>
  )
}
