'use client'
import { useState, useRef, useEffect } from 'react'
import { Send, Search, GitCompare, Loader2, Bot, User } from 'lucide-react'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { useAiChat, useSemanticSearch, useAiCompare } from '@/hooks/useAi'
import { formatPrice } from '@/lib/utils'

interface ChatMessage {
  role: 'user' | 'assistant'
  content: string
}

export function AiTab({ orgSlug, storeSlug }: { orgSlug: string; storeSlug: string }) {
  return (
    <Tabs defaultValue="chat">
      <TabsList className="mb-6">
        <TabsTrigger value="chat" className="gap-2">
          <Bot className="h-4 w-4" />
          Chat Assistant
        </TabsTrigger>
        <TabsTrigger value="search" className="gap-2">
          <Search className="h-4 w-4" />
          Semantic Search
        </TabsTrigger>
        <TabsTrigger value="compare" className="gap-2">
          <GitCompare className="h-4 w-4" />
          Compare Products
        </TabsTrigger>
      </TabsList>

      <TabsContent value="chat">
        <ChatPanel orgSlug={orgSlug} storeSlug={storeSlug} />
      </TabsContent>

      <TabsContent value="search">
        <SearchPanel orgSlug={orgSlug} storeSlug={storeSlug} />
      </TabsContent>

      <TabsContent value="compare">
        <ComparePanel orgSlug={orgSlug} storeSlug={storeSlug} />
      </TabsContent>
    </Tabs>
  )
}

function ChatPanel({ orgSlug, storeSlug }: { orgSlug: string; storeSlug: string }) {
  const [messages, setMessages] = useState<ChatMessage[]>([
    { role: 'assistant', content: 'Hello! I\'m your ShopPilot AI assistant. How can I help you manage your store today?' },
  ])
  const [input, setInput] = useState('')
  const bottomRef = useRef<HTMLDivElement>(null)
  const { mutate: chat, isPending } = useAiChat(orgSlug, storeSlug)

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  const sendMessage = () => {
    if (!input.trim() || isPending) return
    const userMsg: ChatMessage = { role: 'user', content: input.trim() }
    const history = messages.slice(1) // skip greeting
    setMessages((prev) => [...prev, userMsg])
    setInput('')

    chat(
      { message: userMsg.content, history },
      {
        onSuccess: (data) => {
          setMessages((prev) => [
            ...prev,
            { role: 'assistant', content: data.reply ?? data.response ?? data.message ?? 'Got it!' },
          ])
        },
        onError: () => {
          setMessages((prev) => [
            ...prev,
            { role: 'assistant', content: 'Sorry, I encountered an error. Please try again.' },
          ])
        },
      }
    )
  }

  return (
    <div className="flex flex-col rounded-lg border bg-white" style={{ height: '540px' }}>
      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {messages.map((msg, i) => (
          <div key={i} className={`flex gap-3 ${msg.role === 'user' ? 'flex-row-reverse' : ''}`}>
            <div className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white text-xs font-bold ${
              msg.role === 'assistant' ? 'bg-blue-500' : 'bg-slate-600'
            }`}>
              {msg.role === 'assistant' ? <Bot className="h-4 w-4" /> : <User className="h-4 w-4" />}
            </div>
            <div className={`max-w-[75%] rounded-lg px-4 py-2.5 text-sm ${
              msg.role === 'user'
                ? 'bg-blue-500 text-white'
                : 'bg-slate-100 text-slate-800'
            }`}>
              {msg.content}
            </div>
          </div>
        ))}
        {isPending && (
          <div className="flex gap-3">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-500 text-white">
              <Bot className="h-4 w-4" />
            </div>
            <div className="flex items-center gap-2 rounded-lg bg-slate-100 px-4 py-2.5 text-sm text-slate-500">
              <Loader2 className="h-4 w-4 animate-spin" />
              Thinking…
            </div>
          </div>
        )}
        <div ref={bottomRef} />
      </div>

      {/* Input */}
      <div className="border-t p-4">
        <div className="flex gap-2">
          <Input
            placeholder="Ask me anything about your store…"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && sendMessage()}
            disabled={isPending}
            className="flex-1"
          />
          <Button onClick={sendMessage} disabled={isPending || !input.trim()} size="icon">
            <Send className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  )
}

function SearchPanel({ orgSlug, storeSlug }: { orgSlug: string; storeSlug: string }) {
  const [query, setQuery] = useState('')
  const { mutate: search, data, isPending } = useSemanticSearch(orgSlug, storeSlug)

  const handleSearch = () => {
    if (!query.trim()) return
    search({ query: query.trim(), limit: 10 })
  }

  return (
    <div className="space-y-4">
      <div className="flex gap-2">
        <Input
          placeholder="Search products semantically, e.g. 'comfortable winter shoes'…"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
          className="flex-1"
        />
        <Button onClick={handleSearch} disabled={isPending || !query.trim()}>
          {isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}
          <span className="ml-2">Search</span>
        </Button>
      </div>

      {data?.results && data.results.length > 0 ? (
        <div className="rounded-lg border bg-white">
          <table className="w-full text-sm">
            <thead className="border-b bg-slate-50">
              <tr>
                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Product</th>
                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Price</th>
                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Relevance Score</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {data.results.map((r: any, idx: number) => {
                const product = r.product ?? r
                return (
                  <tr key={product.id ?? idx} className="hover:bg-slate-50/50">
                    <td className="px-4 py-3">
                      <div className="font-medium">{product.name}</div>
                      {product.short_description && (
                        <div className="text-xs text-muted-foreground">{product.short_description}</div>
                      )}
                    </td>
                    <td className="px-4 py-3">{product.price ? formatPrice(product.price) : '—'}</td>
                    <td className="px-4 py-3">
                      <Badge variant="secondary">
                        {typeof r.score === 'number' ? (r.score * 100).toFixed(1) + '%' : r.score ?? '—'}
                      </Badge>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      ) : data && (
        <div className="rounded-lg border bg-white p-10 text-center text-muted-foreground">
          No products found matching your query.
        </div>
      )}
    </div>
  )
}

function ComparePanel({ orgSlug, storeSlug }: { orgSlug: string; storeSlug: string }) {
  const [idsInput, setIdsInput] = useState('')
  const { mutate: compare, data, isPending } = useAiCompare(orgSlug, storeSlug)

  const handleCompare = () => {
    const ids = idsInput
      .split(',')
      .map((s) => parseInt(s.trim(), 10))
      .filter((n) => !isNaN(n))
    if (ids.length < 2) return
    compare(ids)
  }

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Enter Product IDs to Compare</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-xs text-muted-foreground">
            Enter comma-separated product IDs (minimum 2), e.g. <code>1, 2, 3</code>
          </p>
          <div className="flex gap-2">
            <Input
              placeholder="e.g. 1, 2, 3"
              value={idsInput}
              onChange={(e) => setIdsInput(e.target.value)}
              className="flex-1"
            />
            <Button onClick={handleCompare} disabled={isPending}>
              {isPending ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <GitCompare className="mr-2 h-4 w-4" />}
              Compare
            </Button>
          </div>
        </CardContent>
      </Card>

      {data && (
        <div className="space-y-4">
          {data.comparison && (
            <Card>
              <CardHeader>
                <CardTitle className="text-sm font-medium">AI Comparison Analysis</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{data.comparison}</p>
              </CardContent>
            </Card>
          )}
          {data.recommendation && (
            <Card className="border-blue-200 bg-blue-50">
              <CardHeader>
                <CardTitle className="text-sm font-medium text-blue-800">✨ Recommendation</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-blue-700 leading-relaxed">{data.recommendation}</p>
              </CardContent>
            </Card>
          )}
        </div>
      )}
    </div>
  )
}
