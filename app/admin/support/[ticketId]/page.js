'use client'

import { useEffect, useState, useRef } from 'react'
import { useRouter, useParams } from 'next/navigation'
import Link from 'next/link'
import { LayoutDashboard, Package, ShoppingBag, LogOut, Headphones, ArrowLeft, Send, Lock, User, Loader2, XCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
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

export default function AdminTicketDetail() {
  const router = useRouter()
  const params = useParams()
  const [loading, setLoading] = useState(true)
  const [ticket, setTicket] = useState(null)
  const [messages, setMessages] = useState([])
  const [newMessage, setNewMessage] = useState('')
  const [sending, setSending] = useState(false)
  const [closing, setClosing] = useState(false)
  const messagesEndRef = useRef(null)

  useEffect(() => {
    const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken')
    if (!token) {
      router.push('/admin/login')
      return
    }
    fetchTicket()
  }, [params.ticketId])

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  const fetchTicket = async () => {
    try {
      const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken')
      const response = await fetch(`/api/admin/support/tickets/${params.ticketId}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      })

      if (response.status === 401 || response.status === 403) {
        localStorage.removeItem('adminToken')
        router.push('/admin/login')
        return
      }

      if (response.status === 404) {
        toast.error('Talep bulunamadı')
        router.push('/admin/support')
        return
      }

      const data = await response.json()
      if (data.success) {
        setTicket(data.data.ticket)
        setMessages(data.data.messages)
      }
    } catch (error) {
      console.error('Error fetching ticket:', error)
      toast.error('Talep yüklenirken hata oluştu')
    } finally {
      setLoading(false)
    }
  }

  const handleSendMessage = async (e) => {
    e.preventDefault()
    if (!newMessage.trim() || newMessage.length < 2) {
      toast.error('Mesaj en az 2 karakter olmalıdır')
      return
    }

    setSending(true)
    try {
      const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken')
      const response = await fetch(`/api/admin/support/tickets/${params.ticketId}/messages`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ message: newMessage })
      })

      const data = await response.json()
      if (data.success) {
        setNewMessage('')
        await fetchTicket()
        toast.success('Yanıt gönderildi')
      } else {
        toast.error(data.error || 'Yanıt gönderilemedi')
      }
    } catch (error) {
      console.error('Send error:', error)
      toast.error('Bir hata oluştu')
    } finally {
      setSending(false)
    }
  }

  const handleCloseTicket = async () => {
    if (!confirm('Bu talebi kapatmak istediğinize emin misiniz?')) return

    setClosing(true)
    try {
      const token = localStorage.getItem('userToken') || localStorage.getItem('adminToken')
      const response = await fetch(`/api/admin/support/tickets/${params.ticketId}/close`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({})
      })

      const data = await response.json()
      if (data.success) {
        toast.success('Talep kapatıldı')
        await fetchTicket()
      } else {
        toast.error(data.error || 'Talep kapatılamadı')
      }
    } catch (error) {
      console.error('Close error:', error)
      toast.error('Bir hata oluştu')
    } finally {
      setClosing(false)
    }
  }

  const handleLogout = () => {
    localStorage.removeItem('adminToken')
    localStorage.removeItem('adminUsername')
    router.push('/admin/login')
  }

  const getStatusBadge = (status) => {
    const config = statusConfig[status] || statusConfig['waiting_admin']
    return (
      <Badge variant="outline" className={`${config.textColor} border-current`}>
        {config.label}
      </Badge>
    )
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center">
        <Loader2 className="w-8 h-8 text-blue-500 animate-spin" />
      </div>
    )
  }

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
            className="w-full justify-start bg-blue-600 hover:bg-blue-700 text-white"
          >
            <Headphones className="w-4 h-4 mr-2" />
            Destek
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
      <div className="ml-64 flex flex-col h-screen">
        {/* Header */}
        <div className="flex-shrink-0 bg-slate-900 border-b border-slate-800 p-4">
          <div className="flex items-start justify-between">
            <div className="flex items-start gap-4">
              <Link
                href="/admin/support"
                className="p-2 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition-colors mt-1"
              >
                <ArrowLeft className="w-5 h-5" />
              </Link>
              <div>
                <div className="flex items-center gap-3 mb-1">
                  <span className="text-slate-500">#{ticket?.id?.slice(-8)}</span>
                  <span className="text-slate-600">•</span>
                  <span className="text-slate-400">{categoryLabels[ticket?.category]}</span>
                  {getStatusBadge(ticket?.status)}
                </div>
                <h1 className="text-xl font-bold text-white">{ticket?.subject}</h1>
                <div className="flex items-center gap-4 mt-2 text-sm">
                  <span className="text-slate-400">
                    <span className="text-slate-500">Kullanıcı:</span> {ticket?.userName}
                  </span>
                  <span className="text-slate-400">
                    <span className="text-slate-500">Email:</span> {ticket?.userEmail}
                  </span>
                </div>
              </div>
            </div>
            {ticket?.status !== 'closed' && (
              <Button
                onClick={handleCloseTicket}
                disabled={closing}
                variant="outline"
                className="border-red-500/50 text-red-400 hover:bg-red-500/10"
              >
                {closing ? (
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                ) : (
                  <XCircle className="w-4 h-4 mr-2" />
                )}
                Talebi Kapat
              </Button>
            )}
          </div>
        </div>

        {/* Messages */}
        <div className="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-950">
          {messages.map((msg) => (
            <div
              key={msg.id}
              className={`flex ${msg.sender === 'admin' ? 'justify-end' : 'justify-start'}`}
            >
              <div className={`max-w-[70%]`}>
                <div className={`flex items-end gap-2 ${msg.sender === 'admin' ? 'flex-row-reverse' : ''}`}>
                  {/* Avatar */}
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${
                    msg.sender === 'admin' 
                      ? 'bg-gradient-to-br from-emerald-500 to-emerald-700' 
                      : 'bg-blue-600'
                  }`}>
                    {msg.sender === 'admin' ? (
                      <Headphones className="w-4 h-4 text-white" />
                    ) : (
                      <User className="w-4 h-4 text-white" />
                    )}
                  </div>
                  
                  {/* Message Bubble */}
                  <div className={`rounded-2xl px-4 py-3 ${
                    msg.sender === 'admin'
                      ? 'bg-emerald-600 text-white rounded-br-md'
                      : 'bg-slate-800 border border-slate-700 text-white rounded-bl-md'
                  }`}>
                    <p className="text-sm whitespace-pre-wrap">{msg.message}</p>
                  </div>
                </div>
                
                {/* Timestamp */}
                <p className={`text-xs text-slate-500 mt-1 ${msg.sender === 'admin' ? 'text-right mr-10' : 'ml-10'}`}>
                  {msg.sender === 'admin' && msg.adminUsername && (
                    <span className="text-emerald-400 mr-1">{msg.adminUsername}</span>
                  )}
                  {new Date(msg.createdAt).toLocaleString('tr-TR', {
                    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
                  })}
                </p>
              </div>
            </div>
          ))}
          <div ref={messagesEndRef} />
        </div>

        {/* Input Area */}
        <div className="flex-shrink-0 bg-slate-900 border-t border-slate-800 p-4">
          {ticket?.status === 'closed' ? (
            <div className="flex items-center justify-center gap-2 py-3 text-slate-500">
              <Lock className="w-4 h-4" />
              <span>Bu talep kapatılmıştır.</span>
            </div>
          ) : (
            <form onSubmit={handleSendMessage} className="flex gap-3">
              <input
                type="text"
                value={newMessage}
                onChange={(e) => setNewMessage(e.target.value)}
                placeholder="Yanıtınızı yazın..."
                className="flex-1 px-4 py-3 rounded-lg bg-slate-800 border border-slate-700 text-white placeholder:text-slate-500 focus:border-blue-500 focus:outline-none"
                disabled={sending}
              />
              <Button
                type="submit"
                disabled={sending || !newMessage.trim()}
                className="bg-emerald-600 hover:bg-emerald-700 text-white px-6"
              >
                {sending ? (
                  <Loader2 className="w-5 h-5 animate-spin" />
                ) : (
                  <Send className="w-5 h-5" />
                )}
              </Button>
            </form>
          )}
        </div>
      </div>
    </div>
  )
}
