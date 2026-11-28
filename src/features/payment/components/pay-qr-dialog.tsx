import { useEffect, useRef, useState, useCallback } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { fetchOrderDetail } from '@/lib/api';

interface PayQrDialogProps {
  open: boolean;
  qrCode: string;
  orderId: number;
  onClose: () => void;
  onSuccess?: () => void;
}

export function PayQrDialog({
  open,
  qrCode,
  orderId,
  onClose,
  onSuccess,
}: PayQrDialogProps) {
  const [polling, setPolling] = useState(true);
  const [paid, setPaid] = useState(false);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const checkOrderStatus = useCallback(async () => {
    try {
      const detail = await fetchOrderDetail(orderId);
      const status = detail?.order_status;
      // OrderStatus: Paid=1, PendingShipment=2, Completed=3
      if (status === 1 || status === 2 || status === 3) {
        setPaid(true);
        setPolling(false);
        if (timerRef.current) {
          clearInterval(timerRef.current);
          timerRef.current = null;
        }
        onSuccess?.();
      }
    } catch {
      // 轮询失败时静默忽略，下次继续
    }
  }, [orderId, onSuccess]);

  useEffect(() => {
    if (!open || !polling) return;
    // 立即查一次，然后每 3 秒轮询
    // eslint-disable-next-line react-hooks/set-state-in-effect -- 首次轮询需立即触发，checkOrderStatus 内部 setState 是预期行为
    checkOrderStatus();
    timerRef.current = setInterval(checkOrderStatus, 3000);
    return () => {
      if (timerRef.current) {
        clearInterval(timerRef.current);
        timerRef.current = null;
      }
    };
  }, [open, polling, checkOrderStatus]);

  useEffect(() => {
    if (!open) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- 关闭弹窗时重置支付状态，逻辑正确但规则误报
      setPaid(false);
      setPolling(true);
    }
  }, [open]);

  const handleClose = () => {
    if (timerRef.current) {
      clearInterval(timerRef.current);
      timerRef.current = null;
    }
    setPolling(false);
    onClose();
  };

  return (
    <Dialog open={open} onOpenChange={(v) => !v && handleClose()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>支付宝扫码支付</DialogTitle>
          <DialogDescription>
            请使用支付宝扫描下方二维码完成支付
          </DialogDescription>
        </DialogHeader>

        <div className="flex flex-col items-center gap-4 py-4">
          {paid ? (
            <div className="flex flex-col items-center gap-2 text-green-600">
              <svg
                className="h-12 w-12"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              <p className="text-lg font-semibold">支付成功</p>
            </div>
          ) : (
            <>
              <div className="rounded-lg border p-4">
                <QRCodeSVG
                  value={qrCode}
                  size={200}
                  level="M"
                  includeMargin={false}
                />
              </div>
              <p className="text-sm text-muted-foreground">
                {polling ? '正在等待支付结果...' : '已停止轮询'}
              </p>
            </>
          )}
        </div>

        <DialogFooter className="flex gap-2">
          {paid ? (
            <Button onClick={handleClose} className="w-full">
              完成
            </Button>
          ) : (
            <>
              <Button
                variant="outline"
                onClick={handleClose}
                className="flex-1"
              >
                取消支付
              </Button>
              <Button
                onClick={() => {
                  checkOrderStatus();
                }}
                className="flex-1"
              >
                我已支付完成
              </Button>
            </>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
