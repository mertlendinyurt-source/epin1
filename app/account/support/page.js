'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { MessageSquarePlus, MessageCircle, Clock, CheckCircle, AlertCircle, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

const categoryLabels = {
  'odeme': 'Ödeme',
  'teslimat': 'Teslimat',
  'hesap': 'Hesap',
  'diger': 'Diğer'
};

const statusConfig = {
  'waiting_admin': { label: 'Admin Yanıtı Bekleniyor', color: 'bg-yellow-500/20 text-yellow-400', icon: Clock },
  'waiting_user': { label: 'Yanıtınız Bekleniyor', color: 'bg-blue-500/20 text-blue-400', icon: AlertCircle },
  'closed': { label: 'Kapatıldı', color: 'bg-gray-500/20 text-gray-400', icon: CheckCircle }
};

export default function SupportTickets() {
  const router = useRouter();
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchTickets();
  }, []);

  const fetchTickets = async () => {
    const token = localStorage.getItem('userToken');
    if (!token) {
      router.push('/');
      return;
    }

    try {
      const response = await fetch('/api/support/tickets', {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      if (response.status === 401) {
        router.push('/');
        return;
      }

      const data = await response.json();
      if (data.success) {
        setTickets(data.data);
      }
    } catch (error) {
      console.error('Fetch error:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const config = statusConfig[status] || statusConfig['waiting_admin'];
    const Icon = config.icon;
    return (
      <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${config.color}`}>
        <Icon className="w-3.5 h-3.5" />
        {config.label}
      </span>
    );
  };

  const getCategoryBadge = (category) => {
    return (
      <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-white/10 text-white/70">
        {categoryLabels[category] || category}
      </span>
    );
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="w-8 h-8 text-blue-500 animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white">Destek Taleplerim</h1>
          <p className="text-white/60 mt-1">Destek taleplerinizi görüntüleyin ve takip edin</p>
        </div>
        <Link href="/account/support/new">
          <Button className="bg-blue-600 hover:bg-blue-700 text-white">
            <MessageSquarePlus className="w-4 h-4 mr-2" />
            Yeni Talep
          </Button>
        </Link>
      </div>

      {/* Tickets List */}
      {tickets.length === 0 ? (
        <div className="bg-[#1e2229] rounded-xl border border-white/10 p-12 text-center">
          <MessageCircle className="w-16 h-16 text-white/20 mx-auto mb-4" />
          <h3 className="text-lg font-semibold text-white mb-2">Henüz destek talebiniz yok</h3>
          <p className="text-white/50 mb-6">Sorularınız veya sorunlarınız için yeni bir destek talebi oluşturabilirsiniz.</p>
          <Link href="/account/support/new">
            <Button className="bg-blue-600 hover:bg-blue-700 text-white">
              <MessageSquarePlus className="w-4 h-4 mr-2" />
              İlk Talebinizi Oluşturun
            </Button>
          </Link>
        </div>
      ) : (
        <div className="space-y-3">
          {tickets.map((ticket) => (
            <Link
              key={ticket.id}
              href={`/account/support/${ticket.id}`}
              className="block bg-[#1e2229] rounded-xl border border-white/10 p-5 hover:border-blue-500/50 transition-all group"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-3 mb-2">
                    {getCategoryBadge(ticket.category)}
                    {getStatusBadge(ticket.status)}
                  </div>
                  <h3 className="text-white font-semibold truncate group-hover:text-blue-400 transition-colors">
                    {ticket.subject}
                  </h3>
                  <p className="text-white/50 text-sm mt-1">
                    Oluşturulma: {new Date(ticket.createdAt).toLocaleDateString('tr-TR', { 
                      year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' 
                    })}
                  </p>
                </div>
                <div className="text-right flex-shrink-0">
                  <p className="text-white/30 text-xs">#{ticket.id.slice(-8)}</p>
                  {ticket.status === 'waiting_user' && (
                    <div className="mt-2 w-3 h-3 bg-blue-500 rounded-full animate-pulse" title="Yanıtınız bekleniyor" />
                  )}
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
