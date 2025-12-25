'use client'

import { useState, useEffect } from 'react'
import { User, Check, X, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
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

  return (
    <div className="min-h-screen" style={{ backgroundColor: '#1A1E24' }}>
      <Toaster position="top-center" richColors />
      
      {/* Header - Plyr style */}
      <header className="sticky top-0 z-50" style={{ backgroundColor: '#12161D', borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
        <div className="max-w-[1400px] mx-auto px-4 h-14 flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <div className="w-7 h-7 rounded bg-blue-600 flex items-center justify-center font-black text-[10px] text-white">
              UC
            </div>
            <span className="text-white font-semibold text-base">PUBG UC</span>
          </div>
            
          <div className="flex items-center gap-2">
            <Button 
              variant="ghost" 
              size="icon" 
              className="text-white/60 hover:text-white hover:bg-white/10 w-8 h-8"
              onClick={() => window.location.href = '/admin/login'}
            >
              <User className="w-4 h-4" />
            </Button>
          </div>
        </div>
      </header>

      {/* Hero - Dark atmospheric gaming background */}
      <div className="relative h-[320px] flex items-center justify-center overflow-hidden" style={{ backgroundColor: '#0F1319' }}>
        <div 
          className="absolute inset-0 bg-cover bg-center opacity-40"
          style={{
            backgroundImage: 'url(https://images.pexels.com/photos/5380620/pexels-photo-5380620.jpeg?auto=compress&cs=tinysrgb&w=1920)'
          }}
        />
        <div className="absolute inset-0" style={{ background: 'linear-gradient(to bottom, rgba(26,30,36,0.4), rgba(26,30,36,0.9))' }} />
        
        <div className="relative z-10 text-center px-4">
          <h1 className="text-4xl md:text-5xl font-black text-white mb-2 tracking-tight leading-tight">
            PUBG MOBILE UC
          </h1>
          <p className="text-base text-white/60 font-medium">Anında teslimat • Güvenli ödeme</p>
        </div>
      </div>

      {/* Products Section - Plyr exact layout */}
      <main className="max-w-[1400px] mx-auto px-4 py-8">
        {loading ? (
          <div className="flex items-center justify-center py-20">
            <Loader2 className="w-7 h-7 text-blue-500 animate-spin" />
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3.5">
            {products.map((product) => (
              <div
                key={product.id}
                onClick={() => handleProductSelect(product)}
                className="group relative rounded overflow-hidden cursor-pointer transition-all hover:scale-[1.02]"
                style={{ backgroundColor: '#25282C', border: '1px solid rgba(255,255,255,0.03)' }}
              >
                {/* Discount Badge */}
                {product.discountPercent > 0 && (
                  <div className="absolute top-1.5 right-1.5 bg-red-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded z-10">
                    -{product.discountPercent}%
                  </div>
                )}

                {/* UC Coin Image */}
                <div className="relative h-32 overflow-hidden flex items-center justify-center" style={{ background: 'linear-gradient(135deg, #1F2226 0%, #25282C 100%)' }}>
                  <img 
                    src="https://images.unsplash.com/photo-1645690364326-1f80098eca66?w=200&h=200&fit=crop"
                    alt="UC"
                    className="w-20 h-20 object-contain opacity-75 group-hover:scale-105 transition-transform"
                  />
                </div>

                {/* Content */}
                <div className="p-3 space-y-1.5">
                  {/* Label */}
                  <div className="text-[10px] text-white/40 font-medium uppercase tracking-wide">MOBILE</div>
                  
                  {/* UC Amount */}
                  <div className="text-xl font-bold text-white leading-none">
                    {product.ucAmount} <span className="text-sm text-white/50 font-normal">UC</span>
                  </div>

                  {/* Region */}
                  <div className="flex items-center gap-1.5 text-[11px]">
                    <span className="text-white/50">🇹🇷 TÜRKİYE</span>
                  </div>
                  
                  <div className="text-[11px] text-green-500 font-medium">Bölgenizde kullanılabilir</div>

                  {/* Prices */}
                  <div className="pt-1">
                    {product.discountPrice < product.price && (
                      <div className="text-xs text-white/30 line-through font-medium">
                        ₺ {product.price.toFixed(2)}
                      </div>
                    )}
                    <div className="text-2xl font-bold text-white leading-none mt-0.5">
                      ₺ {product.discountPrice.toFixed(2)}
                    </div>
                    {product.discountPercent > 0 && (
                      <div className="text-[11px] text-green-500 font-medium mt-0.5">
                        {product.discountPercent}% indirim
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>

      {/* Checkout Dialog - Plyr style */}
      <Dialog open={checkoutOpen} onOpenChange={setCheckoutOpen}>
        <DialogContent className="max-w-3xl p-0 gap-0 overflow-hidden" style={{ backgroundColor: '#1F232A', border: '1px solid rgba(255,255,255,0.08)' }}>
          <DialogHeader className="px-5 py-4" style={{ borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
            <DialogTitle className="text-lg font-bold text-white uppercase tracking-wide">ÖDEME TÜRÜNÜ SEÇİN</DialogTitle>
          </DialogHeader>
          
          <div className="grid grid-cols-1 md:grid-cols-2 divide-x divide-white/5">
            {/* Left: Player Info & Payment Methods */}
            <div className="p-5 space-y-5">
              <div>
                <Label className="text-xs text-white/60 mb-2 block uppercase tracking-wide">Oyuncu ID</Label>
                <div className="relative">
                  <Input
                    placeholder="Oyuncu ID Girin"
                    value={playerId}
                    onChange={(e) => setPlayerId(e.target.value)}
                    className="h-10 px-3 text-sm text-white placeholder:text-white/30 border-white/10 focus:border-blue-500"
                    style={{ backgroundColor: '#12161D' }}
                  />
                  {playerLoading && (
                    <Loader2 className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 animate-spin text-blue-500" />
                  )}
                  {!playerLoading && playerValid === true && (
                    <Check className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-green-500" />
                  )}
                  {!playerLoading && playerValid === false && (
                    <X className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-red-500" />
                  )}
                </div>
              </div>

              {playerName && (
                <div className="px-3 py-2.5 rounded" style={{ backgroundColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.15)', border: '1px solid rgba(16, 185, 129, 0.3)' }}>
                  <div className="flex items-center gap-1.5 text-green-400 mb-0.5 text-xs font-semibold">
                    <Check className="w-3.5 h-3.5" />
                    <span>Oyuncu Bulundu</span>
                  </div>
                  <p className="text-white text-sm font-bold">{playerName}</p>
                </div>
              )}

              <div>
                <Label className="text-xs text-white/60 mb-3 block uppercase tracking-wide">Ödeme yöntemleri</Label>
                <div className="space-y-2.5">
                  <div className="px-4 py-3 rounded flex items-center justify-between cursor-pointer" style={{ backgroundColor: '#12161D', border: '1px solid rgba(255,255,255,0.1)' }}>
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-8 rounded flex items-center justify-center" style={{ backgroundColor: '#1F232A' }}>
                        💳
                      </div>
                      <div>
                        <div className="text-sm font-semibold text-white">Kredi / Banka Kartı</div>
                        <div className="text-xs text-white/50">Anında teslimat</div>
                      </div>
                    </div>
                    <div className="w-5 h-5 rounded-full bg-green-500 flex items-center justify-center">
                      <Check className="w-3 h-3 text-white" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Right: Order Summary */}
            {selectedProduct && (
              <div className="p-5 space-y-4">
                <div>
                  <Label className="text-xs text-white/60 mb-3 block uppercase tracking-wide">Ürün</Label>
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded flex items-center justify-center" style={{ backgroundColor: '#12161D' }}>
                      <img 
                        src="https://images.unsplash.com/photo-1645690364326-1f80098eca66?w=100&h=100&fit=crop"
                        alt="UC"
                        className="w-8 h-8 object-contain opacity-70"
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
                  <Label className="text-xs text-white/60 mb-2 block uppercase tracking-wide">Fiyat detayları</Label>
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

                <div className="pt-3" style={{ borderTop: '1px solid rgba(255,255,255,0.05)' }}>
                  <div className="flex justify-between items-baseline mb-4">
                    <span className="text-sm text-white/60 uppercase tracking-wide">Ödenecek Tutar</span>
                    <span className="text-2xl font-black text-green-400">
                      ₺ {selectedProduct.discountPrice.toFixed(2)}
                    </span>
                  </div>

                  <Button
                    onClick={handleCheckout}
                    disabled={!playerValid || orderProcessing}
                    className="w-full h-11 bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm uppercase tracking-wide"
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
      <footer className="mt-24 py-6" style={{ borderTop: '1px solid rgba(255,255,255,0.03)', backgroundColor: '#12161D' }}>
        <div className="max-w-[1400px] mx-auto px-4">
          <div className="text-center text-white/30 text-xs">
            <p>© 2024 PUBG UC Store. Tüm hakları saklıdır.</p>
            <p className="mt-1.5 text-white/20 text-[11px]">
              Bu site PUBG Mobile ile resmi bir bağlantısı yoktur.
            </p>
          </div>
        </div>
      </footer>
    </div>
  )
}