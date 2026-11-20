import { Badge } from "@/components/ui/badge";

const statusStyles = {
    order: {
        pending: "border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300",
        processing: "border-accent/30 bg-accent/10 text-accent",
        completed: "border-success/30 bg-success/10 text-success",
        cancelled: "border-border bg-muted text-muted-foreground",
        failed: "border-destructive/30 bg-destructive/10 text-destructive",
    },
    payment: {
        pending: "border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300",
        paid: "border-success/30 bg-success/10 text-success",
        failed: "border-destructive/30 bg-destructive/10 text-destructive",
        cancelled: "border-border bg-muted text-muted-foreground",
        refunded: "border-accent/30 bg-accent/10 text-accent",
    },
};

export default function StatusBadge({ status, type = "order" }) {
    const normalizedStatus = String(status ?? "unknown").toLowerCase();

    return (
        <Badge
            variant="outline"
            className={`capitalize ${
                statusStyles[type]?.[normalizedStatus] ??
                "border-border bg-muted text-muted-foreground"
            }`}
        >
            {normalizedStatus.replaceAll("_", " ")}
        </Badge>
    );
}
