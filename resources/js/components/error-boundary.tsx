import { AlertTriangle, RotateCcw } from 'lucide-react';
import { Component } from 'react';
import type { ReactNode } from 'react';
import type { ErrorInfo } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Props {
    children: ReactNode;
}

interface State {
    hasError: boolean;
    message: string;
}

export class ErrorBoundary extends Component<Props, State> {
    state: State = { hasError: false, message: '' };

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, message: error.message };
    }

    componentDidCatch(error: Error, info: ErrorInfo): void {
        console.error('Error capturado por ErrorBoundary:', error, info);
    }

    render() {
        if (this.state.hasError) {
            return (
                <Card className="mx-auto my-16 max-w-md">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5" />
                            Ocurrió un error inesperado
                        </CardTitle>
                        <CardDescription>
                            Esto es un bug del sistema y ya fue registrado para
                            que se corrija. Mientras tanto, recargá la página.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="rounded-md border bg-muted p-3 font-mono text-xs break-all">
                            {this.state.message}
                        </p>
                        <Button onClick={() => window.location.reload()}>
                            <RotateCcw className="mr-2 h-4 w-4" />
                            Recargar página
                        </Button>
                    </CardContent>
                </Card>
            );
        }

        return this.props.children;
    }
}
