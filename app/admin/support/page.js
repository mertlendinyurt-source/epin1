'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { LayoutDashboard, Package, ShoppingBag, LogOut, Headphones, Clock, CheckCircle, AlertCircle, Search, Filter, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Toaster } from '@/components/ui/sonner'
import { toast } from 'sonner'

const categoryLabels = {
  'odeme': 'Ödeme',
  'teslimat': 'Teslimat',
  'hesap': 'Hesap',
  'diger': 'Diğer'
};

const statusConfig = {
  'waiting_admin': { label: 'Yanıt Bekliyor', color: 'bg-yellow-500', textColor: 'text-yellow-500' },
  'waiting_user': { label: 'Kullanıcı Bekleniyor', color: 'bg-blue-500', textColor: 'text-blue-500' },
  'closed': { label: 'Kapatıldı', color: 'bg-gray-500', textColor: 'text-gray-500' }
};

export default function AdminSupport() {
  const router = useRouter()
  const [loading, setLoading] = useState(true)
  const [tickets, setTickets] = useState([])
  const [statusFilter, setStatusFilter] = useState('')
  const [searchTerm, setSearchTerm] = useState('')

  useEffect(() => {
    const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken')
    if (!token) {
      router.push('/admin/login')
      return
    }
    fetchTickets()
  }, [statusFilter])

  const fetchTickets = async () => {
    try {
      const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken')
      const url = statusFilter 
        ? `/api/admin/support/tickets?status=${statusFilter}` 
        : '/api/admin/support/tickets'
      
      const response = await fetch(url, {
        headers: { 'Authorization': `Bearer ${token}` }
      })

      if (response.status === 401 || response.status === 403) {
        localStorage.removeItem('adminToken')
        router.push('/admin/login')
        return
      }

      const data = await response.json()
      if (data.success) {
        setTickets(data.data)
      }
    } catch (error) {
      console.error('Error fetching tickets:', error)
      toast.error('Talepler yüklenirken hata oluştu')
    } finally {
      setLoading(false)
    }
  }

  const handleLogout = () => {
    localStorage.removeItem('adminToken')
    localStorage.removeItem('adminUsername')
    router.push('/admin/login')
  }

  const filteredTickets = tickets.filter(ticket => {
    if (!searchTerm) return true;
    const search = searchTerm.toLowerCase();
    return (
      ticket.subject?.toLowerCase().includes(search) ||
      ticket.userEmail?.toLowerCase().includes(search) ||
      ticket.userName?.toLowerCase().includes(search) ||
      ticket.id?.toLowerCase().includes(search)
    );
  });

  const getStatusBadge = (status) => {
    const config = statusConfig[status] || statusConfig['waiting_admin'];
    return (
      <Badge variant="outline" className={`${config.textColor} border-current`}>
        {config.label}
      </Badge>
    );
  };

  // Counts
  const waitingAdminCount = tickets.filter(t => t.status === 'waiting_admin').length;
  const waitingUserCount = tickets.filter(t => t.status === 'waiting_user').length;
  const closedCount = tickets.filter(t => t.status === 'closed').length;

  return (
    <div className="min-h-screen bg-slate-950">
      <Toaster position="top-center" richColors />
      
      {/* Sidebar */}
      <div className="fixed left-0 top-0 h-full w-64 bg-slate-900 border-r border-slate-800 p-4 overflow-y-auto">
        <div className="flex items-center gap-2 mb-8">
          <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center font-bold text-white">
            UC
          </div>
          <div>
            <div className="text-white font-bold">PINLY</div>
            <div className="text-slate-400 text-xs">Admin Panel</div>
          </div>
        </div>

        <nav className="space-y-2">
          <Button
            onClick={() => router.push('/admin/dashboard')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <LayoutDashboard className="w-4 h-4 mr-2" />
            Dashboard
          </Button>
          <Button
            onClick={() => router.push('/admin/orders')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <ShoppingBag className="w-4 h-4 mr-2" />
            Siparişler
          </Button>
          <Button
            onClick={() => router.push('/admin/products')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <Package className="w-4 h-4 mr-2" />
            Ürünler
          </Button>
          <Button
            onClick={() => router.push('/admin/support')}
            className="w-full justify-start bg-blue-600 hover:bg-blue-700 text-white relative"
          >
            <Headphones className="w-4 h-4 mr-2" />
            Destek
            {waitingAdminCount > 0 && (
              <span className="absolute right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                {waitingAdminCount}
              </span>
            )}
          </Button>
          <Button
            onClick={() => router.push('/admin/settings/payments')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Ödeme Ayarları
          </Button>
          <Button
            onClick={() => router.push('/admin/settings/site')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Site Ayarları
          </Button>
          <Button
            onClick={() => router.push('/admin/settings/regions')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Bölge Ayarları
          </Button>
          <Button
            onClick={() => router.push('/admin/content/pubg')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Oyun İçeriği
          </Button>
          <Button
            onClick={() => router.push('/admin/content/legal')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Kurumsal Sayfalar
          </Button>
          <Button
            onClick={() => router.push('/admin/reviews')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
            Değerlendirmeler
          </Button>
          <Button
            onClick={() => router.push('/admin/settings/footer')}
            variant="ghost"
            className="w-full justify-start text-slate-300 hover:text-white hover:bg-slate-800"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
            </svg>
            Footer Ayarları
          </Button>
        </nav>

        <div className="absolute bottom-4 left-4 right-4">
          <Button
            onClick={handleLogout}
            variant="outline"
            className="w-full border-slate-700 text-slate-300 hover:text-white"
          >
            <LogOut className="w-4 h-4 mr-2" />
            Çıkış Yap
          </Button>
        </div>
      </div>

      {/* Main Content */}
      <div className="ml-64 p-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-white mb-2">Destek Talepleri</h1>
          <p className="text-slate-400">Müşteri destek taleplerini yönetin</p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-3 gap-4 mb-8">
          <div 
            onClick={() => setStatusFilter(statusFilter === 'waiting_admin' ? '' : 'waiting_admin')}
            className={`bg-slate-900 border rounded-xl p-5 cursor-pointer transition-all ${
              statusFilter === 'waiting_admin' ? 'border-yellow-500' : 'border-slate-800 hover:border-slate-700'
            }`}
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-slate-400 text-sm">Yanıt Bekliyor</p>
                <p className="text-2xl font-bold text-yellow-500">{waitingAdminCount}</p>
              </div>
              <Clock className="w-8 h-8 text-yellow-500/30" />
            </div>
          </div>
          <div 
            onClick={() => setStatusFilter(statusFilter === 'waiting_user' ? '' : 'waiting_user')}
            className={`bg-slate-900 border rounded-xl p-5 cursor-pointer transition-all ${
              statusFilter === 'waiting_user' ? 'border-blue-500' : 'border-slate-800 hover:border-slate-700'
            }`}
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-slate-400 text-sm">Kullanıcı Bekleniyor</p>
                <p className="text-2xl font-bold text-blue-500">{waitingUserCount}</p>
              </div>
              <AlertCircle className="w-8 h-8 text-blue-500/30" />
            </div>
          </div>
          <div 
            onClick={() => setStatusFilter(statusFilter === 'closed' ? '' : 'closed')}
            className={`bg-slate-900 border rounded-xl p-5 cursor-pointer transition-all ${
              statusFilter === 'closed' ? 'border-gray-500' : 'border-slate-800 hover:border-slate-700'
            }`}
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-slate-400 text-sm">Kapatıldı</p>
                <p className="text-2xl font-bold text-gray-500">{closedCount}</p>
              </div>
              <CheckCircle className="w-8 h-8 text-gray-500/30" />
            </div>
          </div>
        </div>

        {/* Search & Filter */}
        <div className="flex gap-4 mb-6">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <Input
              placeholder="Konu, kullanıcı veya ID ile ara..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10 bg-slate-900 border-slate-800 text-white placeholder:text-slate-500"
            />
          </div>
          {statusFilter && (
            <Button
              variant="outline"
              onClick={() => setStatusFilter('')}
              className="border-slate-700 text-slate-300"
            >
              Filtreyi Temizle
            </Button>
          )}
        </div>

        {/* Tickets Table */}
        <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
          {loading ? (
            <div className="p-12 text-center">
              <Loader2 className="w-8 h-8 text-blue-500 animate-spin mx-auto" />
            </div>
          ) : filteredTickets.length === 0 ? (
            <div className="p-12 text-center text-slate-400">
              {searchTerm || statusFilter ? 'Sonuç bulunamadı' : 'Henüz destek talebi yok'}
            </div>
          ) : (
            <table className="w-full">
              <thead className="bg-slate-800/50">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase">Kullanıcı</th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase">Konu</th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase">Kategori</th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase">Durum</th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase">Tarih</th>
                  <th className="px-6 py-4 text-right text-xs font-medium text-slate-400 uppercase">İşlem</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800">
                {filteredTickets.map((ticket) => (
                  <tr key={ticket.id} className="hover:bg-slate-800/30 transition-colors">
                    <td className="px-6 py-4">
                      <div>
                        <p className="text-white font-medium">{ticket.userName}</p>
                        <p className="text-slate-500 text-sm">{ticket.userEmail}</p>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <p className="text-white truncate max-w-xs">{ticket.subject}</p>
                      <p className="text-slate-500 text-xs">#{ticket.id.slice(-8)}</p>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-slate-300">{categoryLabels[ticket.category] || ticket.category}</span>
                    </td>
                    <td className="px-6 py-4">
                      {getStatusBadge(ticket.status)}
                    </td>
                    <td className="px-6 py-4 text-slate-400 text-sm">
                      {new Date(ticket.updatedAt).toLocaleDateString('tr-TR')}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <Link href={`/admin/support/${ticket.id}`}>
                        <Button size="sm" variant="outline" className="border-slate-700 text-slate-300 hover:text-white">
                          Görüntüle
                        </Button>
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  )
}
