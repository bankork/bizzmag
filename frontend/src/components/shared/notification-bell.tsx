"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import { Bell } from "lucide-react";
import { cn } from "cn";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { useMarkNotificationsRead, useNotifications } from "@/lib/api/notifications";
import type { AreaKey } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-provider";
import { formatDateTime } from "@/lib/format";
import { notificationLink } from "@/lib/visits";

/**
 * The notifications badge and list (FR-NOT-001), refreshed every 30
 * seconds and whenever the list is opened. Opening one marks it read and
 * goes to the visit it is about.
 */
export function NotificationBell({ area }: { area: AreaKey }) {
  const auth = useAuth();
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const notifications = useNotifications(auth.status === "authenticated");
  const markRead = useMarkNotificationsRead();
  const unread = notifications.data?.unread ?? 0;
  const list = notifications.data?.notifications ?? [];

  return (
    <Dialog
      open={open}
      onOpenChange={(next) => {
        setOpen(next);

        if (next) {
          void notifications.refetch();
        }
      }}
    >
      <DialogTrigger render={<Button variant="ghost" size="sm" className="relative" aria-label={unread > 0 ? `Notifications, ${unread} unread` : "Notifications"} />}>
        <Bell aria-hidden />
        {unread > 0 ? (
          <span className="absolute -top-1 -right-1 flex min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[0.7rem] font-semibold text-primary-foreground tabular-nums" aria-hidden>
            {unread > 99 ? "99+" : unread}
          </span>
        ) : null}
      </DialogTrigger>
      <DialogContent>
        <DialogTitle>Notifications</DialogTitle>
        <DialogDescription>{unread > 0 ? `${unread} unread` : "You are up to date."}</DialogDescription>
        {unread > 0 ? (
          <div className="flex justify-end">
            <Button variant="link" size="sm" onClick={() => markRead.mutate("all")}>
              Mark all as read
            </Button>
          </div>
        ) : null}
        <ul className="max-h-96 divide-y overflow-y-auto rounded-lg border" aria-label="Notifications">
          {list.length === 0 ? <li className="p-4 text-sm text-muted-foreground">Nothing yet. You will hear here about approvals, disputes and work you are added to.</li> : null}
          {list.map((notification) => {
            const link = notificationLink(notification, area);

            return (
              <li key={notification.id}>
                <button
                  type="button"
                  className={cn("w-full space-y-0.5 px-3 py-2.5 text-left hover:bg-muted/50", notification.read ? "text-muted-foreground" : "bg-primary/5")}
                  onClick={() => {
                    if (!notification.read) {
                      markRead.mutate([notification.id]);
                    }

                    if (link) {
                      setOpen(false);
                      router.push(link);
                    }
                  }}
                >
                  <span className={cn("block text-sm", notification.read ? undefined : "font-medium text-foreground")}>{notification.title}</span>
                  {notification.body ? <span className="block text-xs">{notification.body}</span> : null}
                  <span className="block text-xs text-muted-foreground">{formatDateTime(notification.created_at)}</span>
                </button>
              </li>
            );
          })}
        </ul>
      </DialogContent>
    </Dialog>
  );
}
